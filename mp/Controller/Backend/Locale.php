<?php

namespace Mp\Controller\Backend;

use Mp\Core\Controller\Backend;
use Mp\Model\Locale as mLocale;
use Mp\App;

class Locale extends Backend {

    public function __construct($table = 'locale', $alias = 'locale', $template = 'locale') {
        $this->model = new mLocale('locale', 'locale');

        parent::__construct(null, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');

        switch($request->query['action']) {
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
            default:
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
        $config = App::mp('config');
        $request = App::mp('request');

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $alias = $this->model()->alias();

        $available = $config->get('locale.available');
        $scope = $config->get('app.scope');
        $channel = $config->get('app.channel');

        $option = [
            'select' => "{$alias}.id, {$alias}.channel, {$alias}.scope, {$alias}.code, {$alias}.modified, {$alias}.locale_" . implode(", {$alias}.locale_", array_keys($available)),
            'order' => "{$alias}.channel asc, {$alias}.scope asc, {$alias}.code asc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);
        $this->render('index', compact('data', 'available', 'scope', 'channel'));
    }

    public function add() {
        $request = App::mp('request');

        $option = [];
        $alias = $this->model()->alias();

        $target = $this->model()->init();
        if (!empty($request->data[$alias])) {
            $flag = $this->model()->save($request->data[$this->model()->alias()]);

            if ($flag) {
                return $this->redirect(App::load('url')->module());
            }

            $target = $request->data;
        }

        $config = App::mp('config');
        $scope = $config->get('app.scope');
        $channel = $config->get('app.channel');
        $available = $config->get('locale.available');

        return $this->render('input', compact('target', 'available', 'scope', 'channel'));
    }

    public function edit($id = 0) {
        $request = App::mp('request');
        $config = App::mp('config');

        $id = intval($id);

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $this->model()->save($request->data[$alias]);
        }

        $available = $config->get('locale.available');

        $fields = "{$alias}.id, {$alias}.channel, {$alias}.code, {$alias}.scope, {$alias}.modified, {$alias}.locale_" . implode(", {$alias}.locale_", array_keys($available));

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        $scope = $config->get('app.scope');
        $channel = $config->get('app.channel');

        return $this->render('input', compact('target', 'available', 'scope', 'channel'));
    }

    public function delete() {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $pk = $this->model()->pk();

            foreach ($request->data[$alias] as $id => $item) {
                if (array_key_exists($pk, $item)) {
                    $update[$id] = $id;
                }
            }

            $target = implode(',', $update);

            $condition = 'id IN (' . $target . ')';

            $this->model()->delete($condition);
        }

        return $this->back();
    }
}