<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Controller\Backend;

class Page extends Backend {

    public function __construct($model = 'page', $table = 'page', $alias = 'page', $template = '') {
        parent::__construct($model, $table, $alias, $template);
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
            'select' => "{$alias}.id, {$alias}.section, {$alias}.title, {$alias}.status",
            'order' => "{$alias}.id desc",
            'page' => $page
        ];

        $data = $this->paginate($option, true);

        $option = [
            'filter' => [
                'alias' => $alias
            ]
        ];

        $this->render('index', compact('data', 'option'));
    }

    protected function makeFilter($criteria = [], $token = '', $filter = '') {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        $where = [];
        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $where[] = $alias . '.status IN (' . $criteria['status'] . ')';
        }

        if (!empty($criteria['section'])) {
            $section = trim($criteria['section']);
            $where[] = $alias . '.section LIKE "' . $section . '"';
        }

        $list = $data = [];
        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.section",
            'where' => implode(' AND ', $where),
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];
        $data = $this->paginate($option, true);

        $option = [
            'filter' => [
                'alias' => $alias,
                'token' => $token,
                'filter' => $filter
            ]
        ];

        $this->render('search', compact('data', 'option'));
    }

    public function edit($id = 0) {
        $request = App::mp('request');

        $id = intval($id);
        $alias = $this->model()->alias();

        $fields = "{$alias}.id, {$alias}.title, {$alias}.section, {$alias}.content, {$alias}.status, {$alias}.seo_id, {$alias}.file_id";

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
            $target = $request->data;
        }

        $target = $this->master($target, $alias);
        return $this->render('input', compact('target', 'option'));
    }

    public function add() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $target = $this->model()->init();

        $target = array_merge(current($target), App::mp('seo')->target());

        if (!empty($request->data[$alias])) {
            $error = [];

            $flag = $this->save($request->data, $error);
            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');

                return $this->redirect(App::load('url')->module());
            }

            $this->set('error', $error);
            $this->flash('edit', __('m0002', 'Please review your data.'), 'error');

            $target = $this->master($request->data, $alias);
        }

        return $this->render('input', compact('target'));
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