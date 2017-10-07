<?php

namespace Mp\Controller\Backend;

use Mp\App;

class Menu extends Category {

    public function __construct($model = 'menu', $table = 'menu', $alias = 'menu', $template = 'menu') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function group() {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        if (empty($request->name['edit']) == false) {
            $target = $this->editGroup();
        } elseif (empty($request->query[2]) == false) {
            $this->deleteGroup();
        } else {
            $target = $this->addGroup();
        }

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.slug, {$alias}.channel, {$alias}.status",
            'where' => "{$alias}.parent_id = 0"
        ];

        $data = [
            'list' => $this->model()->find($option)
        ];

        $this->render('group', compact('target', 'data'));
    }

    public function add($group = '') {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $target = $this->model()->init();

        if (!empty($request->data[$alias])) {
            $error = [];
            $flag = $this->save($request->data, $error, true, $group);
            if ($flag) {
                return $this->redirect(App::load('url')->module($group));
            }

            $target = $request->data;
        }

        $option = [
            'category' => $this->branch($group),
            'group' => $group
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function edit($group = '', $id = 0) {
        $request = App::mp('request');

        $id = intval($id);
        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $error = [];
            $flag = $this->save($request->data, $error, true, $group);
        }

        $fields = "{$alias}.id, {$alias}.title, {$alias}.slug, {$alias}.parent_id, {$alias}.idx, {$alias}.status, {$alias}.url, {$alias}.caption";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        $option = [
            'category' => $this->branch($group),
            'group' => $group
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function index($group = '') {
        $alias = $this->model()->alias();

        $root = $this->service()->root($group);

        $data = array('list' => []);
        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.url, {$alias}.caption, {$alias}.idx, {$alias}.status, {$alias}.modified",
        ];

        $option = [
            'group' => $group,
            'list' => $this->service()->extract($root, true, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;', $option)
        ];

        $this->render('index', compact('option'));
    }

    public function delete() {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $module = empty($request->query[2]) ? '' : $request->query[2];
            $condition = 'id IN (' . implode(',', $request->data[$alias]) . ')';
            $this->model()->delete($condition, $module);
        }

        return $this->back();
    }

    public function saveSEO($data = [], $target = [], $model = '', $type = '', &$error = []) {
        return true;
    }

    public function saveSearch($data, $group = []) {
        return true;
    }
}