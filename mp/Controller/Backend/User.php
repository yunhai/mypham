<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Lib\Helper\Security;
use Mp\Core\Controller\Backend;

class User extends Backend {

    public function __construct($model = 'user', $table = 'user', $alias = 'user', $template = '') {
        parent::__construct($model, $table, $alias, $template);
        $this->model()->extension();
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
            case 'logout':
                $this->logout();
                break;
            case 'login':
                $this->login();
                break;
            case 'password':
                $this->updatePassword();
                break;
            case 'filter':
                $this->filter();
                break;
            case 'update':
                $this->update($request->query[2]);
                break;
            default:
                $this->index();
                break;
        }
    }

    protected function makeFilter($criteria = [], $token = '', $filter = '', $channel = 1) {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        $groups = $this->groups($channel);
        $where = [];

        if (empty($criteria['group'])) {
            $where[] = $alias . '.group_id IN (' . implode(',', array_keys($groups)) . ')';
        } else {
            $where[] = $alias . '.group_id IN (' . $criteria['group'] . ')';
        }

        if (!empty($criteria['account'])) {
            $account = trim($criteria['account']);
            if (strpos($account, '%') === false) {
                $account = '%' . $account . '%';
            }
            $where[] = '(' .
                    $alias . '.account LIKE "' . $account . '" OR ' .
                    $alias . '.email LIKE "' . $account . '"' .
                ')';
        }

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $where[] = $alias . '.status IN (' . $criteria['status'] . ')';
        }

        $where = implode(' AND ', $where);

        $list = $data = [];
        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.account, {$alias}.email, {$alias}.fullname, {$alias}.group_id, {$alias}.last_login, {$alias}.status, {$alias}.provider",
            'join' => [
                [
                    'table' => 'group',
                    'alias' => 'group',
                    'type' => 'INNER',
                    'condition' => 'group.id = ' . $alias . '.group_id'
                ],
            ],
            'where' => $where,
            'order' => "{$alias}.id desc",
            'page' => $page
        ];
        $data = $this->paginate($option, true);

        $option = [
            'filter' => [
                'alias' => $alias,
                'group' => $groups,
                'token' => $token,
                'filter' => $filter
            ]
        ];

        $this->render('search', compact('data', 'groups', 'option'));
    }

    public function updatePassword($channel = 1) {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        $id = intval($request->query[2]);

        $target = $this->model()->id($id, "{$alias}.id, {$alias}.account, {$alias}.email", $channel);
        if (empty($target)) {
           abort('target not found', 404);
        }

        if (!empty($request->data[$alias])) {
            $error = [];
            $request->data[$alias]['channel'] = $channel;

            $flag = App::load('user', 'service', [$this->model()])
                        ->updatePassword($request->data[$alias], $error);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', [$alias => $error]);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }
        }

        return $this->render('password', compact('target'));
    }

    public function logout() {
        $service = App::load('user', 'service', [$this->model()]);

        $url = $service->logout() ? 'login' : '';
        $this->redirect(App::load('url')->module($url));
    }

    public function delete() {
        $request = App::mp('request');

        if (!empty($request->data[$this->model()->alias()])) {
            $target = implode(',', $request->data[$this->model()->alias()]);

            $condition = 'id IN (' . $target . ')';

            $this->model()->delete($condition);
        }

        return $this->back();
    }

    protected function groups($channel = 1) {
        return App::load('group', 'model')->available($channel);
    }

    public function add($channel = 1) {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $error = [];

            $request->data[$alias]['channel'] = $channel;
            $flag = $this->save($request->data, $error);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
                return $this->redirect(App::load('url')->module());
            }

            $this->set('error', [$alias => $error]);
            $this->flash('edit', __('m0002', 'Please review your data.'), 'error');

            $target = $request->data;
        } else {
            $target = $this->model()->init();
        }


        $gender = App::mp('config')->get('form.gender');
        $option = array('groups' => $this->groups(), 'gender' => $gender);

        return $this->render('input', compact('target', 'option'));
    }

    public function edit($id = 0, $channel = 1) {
        $alias = $this->model()->alias();

        $id = intval($id);
        $fields = "{$alias}.id, {$alias}.account, {$alias}.email, {$alias}.fullname, {$alias}.group_id, {$alias}.status";

        $target = $this->model()->id($id, $fields, $channel);
        if (empty($target)) {
            abort('target not found', 404);
        }

        $request = App::mp('request');

        if (!empty($request->data[$alias])) {
            $error = [];
            $request->data[$alias]['channel'] = $channel;
            $flag = $this->save($request->data, $error);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', [$alias => $error]);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }

            $target = array_merge($target, $request->data);
        }

        $gender = App::mp('config')->get('form.gender');
        $option = array('groups' => $this->groups(), 'gender' => $gender);

        return $this->render('input', compact('target', 'option'));
    }

    public function lastCheck(&$data = []) {
    }

    private function encryptPassword(&$data = []) {
        if (isset($data['password'])) {
            $security = new security();
            $data['password'] = $security->hash($data['password']);
        }
    }

    public function save($data = [], &$error = [], $validator = true, $rule = 'def') {
        $alias = $this->model()->alias();

        if ($validator) {
            $flag = $this->validate($alias, $data[$alias], $error, 1, $rule);
            if (!$flag) {
                return false;
            }
        }

        $this->encryptPassword($data[$alias]);

        return $this->model()->save($data[$alias]);
    }

    public function index($channel = 1) {
        $request = App::mp('request');

        $groups = $this->groups($channel);
        $groupId = implode(',', array_keys($groups));

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $alias = $this->model()->alias();
        $option = [
            'select' => "{$alias}.id, {$alias}.account, {$alias}.email, {$alias}.fullname, {$alias}.group_id, {$alias}.status, {$alias}.provider",
            'where' => "{$alias}.group_id IN ({$groupId})",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);

        $option = [
            'filter' => [
                'alias' => $alias,
                'group' => $groups
            ]
        ];

        $this->render('index', compact('data', 'groups', 'option'));
    }

    public function login() {
        $request = App::mp('request');
        $option = [];
        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            extract($request->data[$alias]);

            $service = App::load('user', 'service', [$this->model()]);
            $flag = $service->login($account, $password);

            if ($flag) {
                $this->redirect(App::load('url')->full());
            } else {
                $option['error'] = array($alias => array('Account or password you entered is incorrect'));
            }
        }

        $this->render('login', compact('option'));
    }
}
