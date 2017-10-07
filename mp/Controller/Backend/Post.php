<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Core\Controller\Backend;

class Post extends Backend {

    public function __construct($model = 'post', $table = 'post', $alias = 'post', $template = '') {
        parent::__construct($model, $table, $alias, $template);

        $this->model()->category(App::category()->flat($alias));
    }

    public function navigator() {
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
            default :
                $this->index();
                break;
        }
    }

    public function index() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.created, {$alias}.modified, {$alias}.category_id",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);
        $data['category'] = $this->model()->category();

        $option = [
            'filter' => [
                'alias' => $alias,
                'category' => App::category()->flat($alias, false, 'title', '&nbsp;&nbsp;&nbsp;')
            ]
        ];

        $this->render('index', compact('data', 'option'));
    }

    public function edit($id = 0) {
        $request = App::mp('request');

        $id = intval($id);

        $alias = $this->model()->alias();

        $fields = "{$alias}.id, {$alias}.title, {$alias}.category_id, {$alias}.idx, {$alias}.content, {$alias}.status, {$alias}.seo_id, {$alias}.file_id";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        $seo = App::mp('seo')->target($target[$alias]['seo_id']);
        $target = array_merge($target, $seo);

        if (!empty($request->data[$alias])) {
            $error = [];
            $flag = $this->save($request->data, $error);
            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', $error);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }
            $target = $this->formatPostData($request->data, $alias);
        }

        $this->attach($target, $alias);

        $option = [
            'category' => $this->getCategory($alias, true, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;')
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function add() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $target = $this->model()->init();
        $target = array_merge($target, App::mp('seo')->target());

        if (!empty($request->data[$alias])) {
            $error = [];
            if (empty($request->data[$alias]['category_id'])) {
                $category_id = $this->model()->category();
                reset($category_id);
                $category_id = key($category_id);

                $request->data[$alias]['category_id'] = $category_id;
            }
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

    public function delete() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $condition = 'id IN (' . implode(',', $request->data[$alias]) . ')';
            $this->model()->delete($condition, ['seo_id', 'file_id']);
        }

        return $this->back();
    }
}