<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Core\Controller\Backend;

class Seo extends Backend {

    public function __construct($model = 'seo', $table = 'seo', $alias = 'seo', $template = '') {
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
            default :
                $this->index();
                break;
        }
    }

    public function modify() {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        if (empty($request->data[$alias]) == false) {

            $update = [];
            $pk = $this->model()->pk();

            foreach ($request->data[$alias] as $id => $item) {
                if (array_key_exists($pk, $item)) {
                    $update[$id] = $item;
                }
            }

            if ($update) {
                $this->model()->save($update);
            }
        }

        return $this->back();
    }

    public function index() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];


        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.url, {$alias}.alias, {$alias}.title, {$alias}.keyword, {$alias}.status, {$alias}.target_model",
            'order' => "{$alias}.target_model, {$alias}.url",
            'page' => $page,
        ];
        $data = $this->paginate($option, true);

        $this->render('index', compact('data'));
    }

        public function delete() {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $target = [];
            $pk = $this->model()->pk();

            foreach ($request->data[$alias] as $id => $item) {
                if (array_key_exists($pk, $item)) {
                    $target[$id] = $id;
                }
            }

            if ($target) {
                $target = implode(',', $target);
                $condition = $pk . " IN (" . $target . ')';
                $this->model()->delete($condition);
            }
        }

        return $this->redirect(App::load('url')->module());
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

        $fields = "{$alias}.id, {$alias}.status, {$alias}.url, {$alias}.alias, {$alias}.canonical, {$alias}.title, {$alias}.keyword, {$alias}.description";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        return $this->render('input', compact('target'));
    }
}