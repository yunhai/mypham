<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Backend\Extension;

class RatingController extends Extension {

    public function __construct($model = 'productRating', $table = 'extension', $alias = 'rating', $template = 'rating') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'update':
                $this->update($request->query[2]);
                break;
            case 'delete':
                $this->delete();
                break;
            default:
                $this->index();
                break;
        }
    }

    public function index() {
        $request = App::mp('request');

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => 'id, created',
            'page' => $page,
        ];

        $pf = App::load('productRating', 'model');
        $pf->init($pf->field());

        $model = [
            'product-rating' => $pf,
        ];

        $data = App::load('extension', 'service')->paginate($model, $option);
        $option = [
            'origin' => $this->origin($data['list'])
        ];

        $this->render('index', compact('data', 'option'));
    }

    public function origin($data) {
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

    public function update($status = 0) {
        $request = App::mp('request');

        $map = $this->status();
        $alias = $this->model()->alias();

        if (isset($map[$status]) && !empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productRating', 'extension', 'productrating']);
            $f = array_search('status', $service->model()->field());
            $fields = [
                $f => $status
            ];

            $service->update($fields, $request->data[$alias]);
        }

        $this->flash('edit', __('m0003', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function delete() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productRating', 'extension', 'productrating']);
            $service->delete($request->data[$alias]);
        }

        $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function status($alias = '') {
        return App::mp('config')->get('status.default');
    }
}
