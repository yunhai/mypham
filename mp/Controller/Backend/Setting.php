<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Controller\Backend;

class Setting extends Backend {

    public function __construct($model = 'setting', $table = 'setting', $alias = 'setting', $template = '') {
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
            case 'modify':
                $this->modify();
                break;
            case 'update':
                $this->update($request->query[2]);
                break;
            case 'filter':
                $this->filter();
                break;
            case 'advance':
                $this->advance();
                break;
            default:
                $this->index();
                break;
        }
    }

    public function add() {
        $request = App::mp('request');

        $option = [];
        $alias = $this->model()->alias();

        $target = $this->model()->init();
        if (!empty($request->data[$alias])) {
            $flag = $this->model()->save($request->data[$alias]);

            if ($flag) {
                return $this->redirect(App::load('url')->module());
            }

            $target = $request->data;
        }

        return $this->render('input', compact('target'));
    }

    public function edit($id = 0) {
        $request = App::mp('request');

        $id = intval($id);

        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $this->model()->save($request->data[$alias]);
        }

        $fields = "{$alias}.id, {$alias}.status, {$alias}.key, {$alias}.value";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        return $this->render('input', compact('target'));
    }

    public function update($status) {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        $map = $this->status($alias);
        if (isset($map[$status])) {
            $fields = [
                'status' => $status
            ];

            $update = Hash::combine($request->data[$alias], '{n}.id', '{n}.id');
            if ($update) {
                $condition = $alias . '.id IN (' . implode(',', $update) . ')';
                $this->model()->modify($fields, $condition);
            }
        }

        return $this->back();
    }

    public function modify() {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        if (empty($request->data[$alias]) == false) {
            $pk = $this->model()->pk();

            foreach ($request->data[$alias] as $id => $item) {
                if (array_key_exists($pk, $item)) {
                    $this->model()->save($item);
                }
            }
        }

        return $this->back();
    }

    public function delete() {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $this->model()->begin();

            $target = Hash::combine($request->data[$alias], '{n}.id', '{n}.id');
            if ($target) {
                $condition = $alias . '.id IN (' . implode(',', $target) . ')';
                $this->model()->delete($condition);
            }
        }

        return $this->redirect(App::load('url')->module());
    }

    public function index() {
        $request = App::mp('request');

        $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $alias = $this->model()->alias();

        $option = [
            'select' => "{$alias}.id, {$alias}.key, {$alias}.value",
            'order' => "{$alias}.key",
            'where' => $alias . '.status > 0',
            'page' => $page
        ];

        $data = $this->paginate($option);
        $option = [
            'view' => 'basic'
        ];

        $this->render('index', compact('data', 'option'));
    }

    public function advance() {
        $request = App::mp('request');

        $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $alias = $this->model()->alias();

        $option = [
            'select' => "{$alias}.id, {$alias}.key, {$alias}.value, {$alias}.status",
            'order' => "{$alias}.key",
            'page' => $page
        ];

        $data = $this->paginate($option);

        $option = [
            'view' => 'advance'
        ];
        $this->render('advance', compact('data', 'option'));
    }
}
