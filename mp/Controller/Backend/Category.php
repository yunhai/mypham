<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Lib\Utility\Text;
use Mp\Core\Controller\Backend;

class Category extends Backend {
    private $service = null;

    public function __construct($model = 'category', $table = 'category', $alias = 'category', $template = '') {
        parent::__construct($model, $table, $alias, $template);

        $this->service = App::load($model, 'service', [$this->model()]);
    }

    public function lastCheck(&$data = []) {
    }

    public function service($service = null) {
        if (is_null($service)) {
            return $this->service;
        }
        return $this->service = $service;
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'add':
                    $this->add($request->query[2]);
                break;
            case 'edit':
                     $this->edit($request->query[2], $request->query[3]);
                break;
            case 'update':
                    $this->update($request->query[2]);
                break;
            case 'delete':
                    $this->delete();
                break;
            case 'group':
                    $this->group();
                break;
            default:
                    $group = empty($request->query[1]) ? '' : $request->query[1];
                    $this->index($group);
                break;
        }
    }

    public function update($status) {
        $request = App::mp('request');
        $alias = $this->model()->alias();
        $map = $this->status($alias);

        if (array_key_exists($status, $map)) {
            $target = [];
            foreach ($request->data[$alias] as $id) {
                if (in_array($id, $target)) {
                    continue;
                }

                $tmp = $this->service->extract($id, false);
                $target = array_merge($target, array_keys($tmp));
            }

            if ($target) {
                $fields = [
                    'status' => $status
                ];

                $condition = "id IN (" . implode(',', $target) . ')';
                $this->model()->modify($fields, $condition);
            }
        }

        return $this->back();
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
            'select' => "{$alias}.id, {$alias}.title, {$alias}.slug, {$alias}.status",
            'where' => "{$alias}.parent_id = 0"
        ];

        $data = [
            'list' => $this->model()->find($option)
        ];

        $this->render('group', compact('target', 'data'));
    }

    protected function deleteGroup() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $target = implode(',', $request->data[$alias]);

            $condition = 'id IN (' . $target . ')';

            $this->model()->delete($condition);
        }

        return $this->back();
    }

    protected function editGroup() {
        $request = App::mp('request');

        $id = intval($request->name['edit']);
        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            if (!isset($request->data[$alias]['status'])) {
                $request->data[$alias]['status'] = 0;
            }

            $error = [];
            $flag = $this->save($request->data, $error);
            if ($flag) {
                $this->redirect(App::load('url')->module('group'));
            }
        }

        $fields = "{$alias}.id, {$alias}.title, {$alias}.slug, {$alias}.status";
        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        return $target;
    }

    protected function addGroup() {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        if (empty($request->data[$alias])) {
            return $this->model()->init();
        }

        if (!isset($request->data[$alias]['status'])) {
            $request->data[$alias]['status'] = 0;
        }

        $error = [];
        $flag = $this->save($request->data, $error, true);

        if ($flag) {
            $this->redirect(App::load('url')->module('group'));
        }

        return $request->data;
    }

    public function index($group = '') {
        $alias = $this->model()->alias();

        $root = $this->service->root($group);
        $data = ['list' => []];
        $default = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.slug, {$alias}.status, {$alias}.idx",
        ];

        $option = [
            'list' => $this->service->extract($root, true, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;', $default),
            'group' => $group,
            'status' => $this->status($alias),
        ];

        $this->render('index', compact('data', 'option'));
    }

    public function add($group = '') {
        $request = App::mp('request');

        $option = [
            'group' => $group,
            'category' => $this->branch($group)
        ];

        if (empty($option['category'])) {
            abort('NotFoundException');
        }

        $alias = $this->model()->alias();
        $target = $this->model()->init();
        $target = array_merge($target, App::mp('seo')->target());

        if (!empty($request->data[$alias])) {
            $error = [];
            $flag = $this->save($request->data, $error, true, $group);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
                return $this->redirect(App::load('url')->module($group));
            }

            $this->set('error', $error);
            $this->flash('edit', __('m0002', 'Please review your data.'), 'error');

            $target = $this->formatPostData($request->data, $alias);
        }

        $this->attach($target, $alias);

        return $this->render('input', compact('target', 'option'));
    }


    public function edit($group = '', $id = 0) {
        $alias = $this->model()->alias();
        $request = App::mp('request');

        $id = intval($id);
        $fields = "{$alias}.id, {$alias}.title, {$alias}.slug, {$alias}.parent_id, {$alias}.idx, {$alias}.status, {$alias}.seo_id";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        $seo = App::mp('seo')->target($target[$alias]['seo_id']);
        $target = array_merge($target, $seo);

        if (!empty($request->data[$alias])) {
            $error = [];
            $flag = $this->save($request->data, $error, true, $group);
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
            'group' => $group,
            'category' => $this->branch($group)
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function save($data = [], &$error = [], $validate = true, $group = '') {
        $alias = $this->model()->alias();

        if (empty($data[$alias]['slug'])) {
            $data[$alias]['slug'] = Text::slug($data[$alias]['title']);
        } else {
            $data[$alias]['slug'] = strtolower($data[$alias]['slug']);
        }

        if (empty($group)) {
            $group = $data[$alias]['slug'];
        }

        $select = "{$alias}.id, {$alias}.slug";
        $where = "{$alias}.slug = '{$group}' AND {$alias}.status > 0";
        $tmp = $this->model()->find(compact('select', 'where'), 'first');

        $module = '';
        $root = $data[$alias]['tree_id'] = 0;

        if ($tmp) {
            $root = $data[$alias]['tree_id'] = $tmp[$alias]['id'];
            $module = $tmp[$alias]['slug'];
        }

        if ($validate) {
            $flag = $this->validate($alias, $data[$alias], $error);

            if (!$flag) {
                $error = [
                    $alias => $error
                ];
                $this->set('error', $error);
                return false;
            }
        }

        $data[$alias]['group'] = $group;

        $this->model()->begin();
        $flag = parent::save($data, $error, false);
        if ($flag == false) {
            $this->set('error', $error);
            return false;
        }

        $currentId = empty($data[$alias]['id']) ? $this->model()->lastInsertId() : $data[$alias]['id'];

        if (empty($root)) {
            $temp['id'] = $currentId;
            $temp['tree_id'] = $currentId;

            $temp['lft'] = 1;
            $temp['rght'] = 2;

            $flag = $this->model()->save($temp);
            if ($flag == false) {
                return false;
            }
        } else {
            $flag = $this->model()->rebuild($root);
            if ($flag == false) {
                return false;
            }
        }

        $this->model()->commit();

        return true;
    }

    public function saveSEO($data = [], $target = [], $model = '', $type = 'category', &$error = []) {
        $model = empty($target['group']) ? $model : $target['group'];

        $flag = App::mp('seo')->save($data, $target, $model, 'category', $error);

        if ($flag) {
            $option = [
                'fields' => ['seo_id' => $data['id']],
                'where' => 'id = ' . $target['id']
            ];

            return $this->model()->update($option);
        }

        return false;
    }

    protected function branch($group, $display = 'title', $indent = '&nbsp;&nbsp;&nbsp;&nbsp;') {
        return $this->service->flat($group, false, $display, $indent);
    }

    public function delete() {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $module = empty($request->query[2]) ? '' : $request->query[2];
            $condition = 'id IN (' . implode(',', $request->data[$alias]) . ')';
            $this->model()->delete($condition, $module, ['seo_id']);
        }

        return $this->back();
    }

    public function saveSearch($data, $group = []) {
        return true;
    }
}
