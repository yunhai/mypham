<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Backend\Extension;

class FaqController extends Extension
{
    public function __construct($model = '', $table = '', $alias = '', $template = 'faq')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'update':
                $this->update($request->query[2]);
                break;
            case 'delete':
                $this->delete();
                break;
            case 'edit':
                $this->edit($request->query[2]);
                break;
            default:
                $this->index();
                break;
        }
    }

    public function edit($id = 0)
    {
        $request = App::mp('request');
        $id = (int) $id;

        $service = App::load('extension', 'service', ['productFaq', 'extension', 'faq']);
        $service->model()->init($service->model()->field());

        $target = $service->detail($id);

        if (empty($target)) {
            abort('NotFoundException');
        }

        list($model) = explode('-', $target['target_model']);
        $model = App::load($model, 'model');

        $alias = $service->model()->alias();
        if (!empty($request->data[$alias])) {
            $error = [];

            $flag = $service->save($request->data[$alias], $error, $alias);

            if ($flag) {
                $data = array_merge($target, $request->data[$alias]);
                $this->sendEmail($data);
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', $error);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }

            $tmp = $this->formatPostData($request->data, $alias)[$alias];
            $target = array_merge($target, $tmp);
        }

        $option = [
            'category' => App::category()->flat($model->alias(), false, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;')
        ];

        return $this->render('input', compact('target', 'option', 'alias'));
    }

    public function sendEmail($data = [])
    {
        if ($data['send_mail'] && $data['email']) {
            $common = App::load('common');

            $info = [
                'to' => $data['email']
            ];
            $common->sendEmail('981001', $data, $info);
        }

        return true;
    }

    public function index()
    {
        $request = App::mp('request');

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => 'id, created',
            'page' => $page,
        ];

        $pf = App::load('productFaq', 'model');
        $pf->init($pf->field());

        $model = [
            'product-faq' => $pf,
        ];

        $service = App::load('extension', 'service');
        $data = $service->paginate($model, $option);
        $option = [
            'origin' => $this->origin($data['list'])
        ];

        $alias = 'Faq';
        $this->render('index', compact('data', 'option', 'alias'), 'faq');
    }

    public function origin($data)
    {
        $tmp = [];
        foreach ($data as $id => $item) {
            $tmp[$item['target_model']][$item['target_id']] = $item['target_id'];
        }

        $option = [
            'select' => 'id, title',
            'order' => 'id asc'
        ];

        $result = [];
        $category = App::category();
        foreach ($tmp as $model => $item) {
            list($m) = explode('-', $model);

            $option['where'] = 'id IN (' . implode(',', $item) . ')';

            $object = App::load($m, 'model');
            $object->category($category->flat($object->alias()));
            $record = $object->find($option);
            $record = Hash::combine($record, '{n}.' . $m . '.id', '{n}.' . $m);

            $result[$model] = Hash::insert($record, '{n}.model', $m);
        }

        return $result;
    }

    public function update($status = 0)
    {
        $request = App::mp('request');

        $alias = 'faq';
        $map = $this->status();

        if (isset($map[$status]) && !empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productFaq', 'extension', 'faq']);

            $f = array_search('status', $service->model()->field());
            $fields = [
                $f => $status
            ];

            $service->update($fields, $request->data[$alias]);
        }

        $this->flash('edit', __('m0003', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function delete()
    {
        $request = App::mp('request');

        $alias = 'faq';
        if (!empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productFaq', 'extension', 'faq']);
            $service->delete($request->data[$alias]);
        }

        $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function status($alias = '')
    {
        return App::mp('config')->get('status.default');
    }
}
