<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Core\Controller\Backend;

class Order extends Backend
{
    public function __construct($model = 'order', $table = 'order', $alias = 'order', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'detail':
                $this->detail($request->query[2]);
                break;
            case 'delete':
                $this->delete();
                break;
            case 'execute':
                $this->handle($request->query[2], 1);
                break;
            case 'finish':
                $this->handle($request->query[2], 2);
                break;
            case 'reject':
                $this->handle($request->query[2], 3);
                break;
            case 'rollback':
                $this->handle($request->query[2]);
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

    public function update($status)
    {
        $request = App::mp('request');
        $alias = $this->model()->alias();
        $map = $this->status($alias);

        if (isset($map[$status])) {
            $fields = [
                'status' => $status
            ];

            $list = $request->data[$alias];
            foreach ($list as $id) {
                $this->makeHandle($id, $status);
            }
        }

        return $this->back();
    }

    public function handle($id, $status = 0)
    {
        $this->makeHandle($id, $status);

        return $this->redirect(App::load('url')->module('detail/' . $id));
    }

    protected function makeHandle($id, $status = 0)
    {
        $fields = [
            'status' => $status
        ];

        $condition = 'id = ' . $id ;
        $this->model()->modify($fields, $condition);

        return true;
    }

    public function index()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.code, {$alias}.total, {$alias}.tax, {$alias}.total, {$alias}.status, {$alias}.recipient, {$alias}.modified, {$alias}.created",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);
        foreach ($data['list'] as $id => &$item) {
            if ($item[$alias]['recipient']) {
                $item[$alias]['recipient'] = json_decode($item[$alias]['recipient']);
            } else {
                $item[$alias]['recipient'] = [
                    'address' => ''
                ];
            }
        }

        $option = [
            'status' => $this->status($alias),
            'filter' => [
                'alias' => $alias,
            ]
        ];

        $this->render('index', compact('data', 'option'));
    }

    public function makeFilter($criteria = [], $token = '', $filter = '')
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $where = $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $where [] = $alias . '.status IN (' . $criteria['status'] . ')';
        }

        if (!empty($criteria['code'])) {
            $where [] = $alias . '.code LIKE "%' . $criteria['code'] . '%"';
        }

        $where = implode(' AND ', $where);

        $option = [
            'select' => "{$alias}.id, {$alias}.code, {$alias}.total, {$alias}.tax, {$alias}.total, {$alias}.status, {$alias}.recipient, {$alias}.modified, {$alias}.created",
            'order' => "{$alias}.id desc",
            'where' => $where,
            'page' => $page,
        ];
        $data = $this->paginate($option, true);

        foreach ($data['list'] as $id => &$item) {
            if ($item[$alias]['recipient']) {
                $item[$alias]['recipient'] = json_decode($item[$alias]['recipient']);
            } else {
                $item[$alias]['recipient'] = [
                    'address' => ''
                ];
            }
        }

        $option = [
            'status' => $this->status($alias),
            'filter' => [
                'alias' => $alias,
                'token' => $token,
                'filter' => $filter
            ]
        ];

        $this->render('search', compact('data', 'option'));
    }

    public function detail($id = 0)
    {
        $id = (int) $id;

        $alias = $this->model()->alias();

        $fields = "{$alias}.id, {$alias}.user_id, {$alias}.code, {$alias}.total, {$alias}.tax, {$alias}.sub_total, {$alias}.status, {$alias}.recipient, {$alias}.note, {$alias}.modified, {$alias}.created";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        if ($target['order']['recipient']) {
            $target['order']['recipient'] = json_decode($target['order']['recipient']);
        }

        $this->model()->attactCart();
        $target['detail'] = $this->model()->cart($id);

        $target['order']['shipping'] = $target['order']['tax'];
        $option = [
            'status' => $this->status($alias),
        ];

        return $this->render('detail', compact('target', 'option'));
    }

    public function delete()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $request->data[$alias] = array_filter($request->data[$alias]);
            $condition = 'id IN (' . implode(',', $request->data[$alias]) . ')';
            $this->model()->delete($condition);
        }

        return $this->back();
    }
}
