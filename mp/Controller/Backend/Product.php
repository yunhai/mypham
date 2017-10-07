<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Controller\Backend;

class Product extends Backend
{
    public function __construct($model = 'product', $table = 'product', $alias = 'product', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);

        $this->model()->category(App::category()->flat($alias));
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'add':
                $this->add();
                break;
            case 'edit':
                 $this->edit($request->query[2]);
                break;
            case 'delete':
                $this->delete();
                break;
            case 'update':
                $this->update($request->query[2]);
                break;
            case 'filter':
                $this->filter();
                break;
            default:
                $this->index();
                break;
        }
    }

    public function index()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.price, {$alias}.category_id, {$alias}.modified, {$alias}.created",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);

        $data['category'] = $this->model()->category();

        $this->render('index', compact('data'));
    }

    public function edit($id = 0)
    {
        $request = App::mp('request');

        $id = (int) $id;

        $alias = $this->model()->alias();
        $fields = "{$alias}.id, {$alias}.title, {$alias}.category_id, {$alias}.idx, {$alias}.price, {$alias}.content, {$alias}.status, {$alias}.seo_id, {$alias}.file_id";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        $seo = App::mp('seo')->target($target[$alias]['seo_id']);
        $target = array_merge($target, $seo);

        if (!empty($request->data[$alias])) {
            $request->data[$alias] = $this->formatData($request->data[$alias]);
            $flag = $this->save($request->data, true);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', [$alias => $error]);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }
            $target = $this->formatPostData($request->data, $alias);
        }

        $this->attach($target);

        $option = [
            'category' => $this->getCategory($alias, true, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;')
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function add()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $target = $this->model()->init();
        $target[$alias]['code'] = mb_strtoupper(dechex(time()));

        $target = array_merge($target, App::mp('seo')->target());
        if (!empty($request->data[$alias])) {
            $error = [];

            $request->data[$alias] = $this->formatData($request->data[$alias]);
            $flag = $this->save($request->data, $error);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');

                return $this->redirect(App::load('url')->module());
            }

            $this->set('error', $error);
            $this->flash('edit', __('m0002', 'Please review your data.'), 'error');

            $target = $this->formatPostData($request->data, $alias);
        }

        $this->attach($target, $alias);
        $option = [
            'category' => $this->getCategory($alias)
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function formatData($data = [])
    {
        if (isset($data['option'])) {
            foreach ($data['option'] as $index => $value) {
                if (!($value) || empty($data['option_price'][$index])) {
                    unset($data['option'][$index], $data['option_price'][$index]);
                }
            }

            $data['option_promotion'] = array_intersect_key($data['option_promotion'], $data['option']);
        }

        return $data;
    }

    public function delete()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $request->data[$alias] = array_filter($request->data[$alias]);

            $ids = implode(',', $request->data[$alias]);
            $condition = 'id IN (' . $ids . ')';
            $this->model()->delete($condition, ['seo_id', 'file_id']);

            $model = App::load('file', 'model');

            $condition = "target_id IN ({$ids}) AND target_model = '{$alias}-files'";
            $model->delete($condition);
        }

        return $this->back();
    }
}
