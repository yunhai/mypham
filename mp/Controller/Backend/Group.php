<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Core\Controller\Backend;

class Group extends Backend {

    public function __construct($model = 'group', $table = 'group', $alias = 'group', $template = '') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'index':
                    $this->index();
                break;
            case 'add':
                    $this->add();
                break;
            case 'edit':
                    $this->edit($request->query[2]);
                break;
            case 'delete':
                    $this->delete();
                break;

            default:
                    $this->index();
                break;
        }
    }

    public function delete() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $target = implode(',', $request->data[$alias]);

            $condition = 'id IN (' . $target . ')';
            $this->model()->delete($condition);
        }

        return $this->back();
    }

    public function add() {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        $target = $this->model()->init();
        if (!empty($request->data[$alias])) {
            $error = [];
            $flag = $this->save($request->data, $error);

            if ($flag) {
                return $this->redirect(App::load('url')->module());
            }

            $this->set('error', [$alias => $error]);

            $target = $request->data;
        }

        return $this->render('input', compact('target'));
    }

    public function edit($id = 0) {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        $id = intval($id);

        if (!empty($request->data[$alias])) {
            $error = [];
            $flag = $this->save($request->data, $error);
            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', [$alias => $error]);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }
        }

        $fields = "{$alias}.id, {$alias}.title, {$alias}.status";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('target not found', 404);
        }

        return $this->render('input', compact('target'));
    }


    public function save($data = [], &$error = [], $validator = true) {
        $alias = $this->model()->alias();

        if ($validator) {
            $flag = $this->validate($alias, $data[$alias], $error);

            if (!$flag) {
                return false;
            }
        }

        $lastInsertId = 0;
        return $this->model()->save($data[$alias], $lastInsertId);
    }

    public function index() {
        $request = App::mp('request');

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $alias = $this->model()->alias();
        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.channel",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);

        $this->render('index', compact('data'));
    }
}