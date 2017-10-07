<?php

namespace Mp\Lib\Traits;

use Mp\App;
use Mp\Lib\Helper\Security;
use Mp\Lib\Utility\Hash;

trait Search {
    public function filter($func = 'makeFilter') {
        $request = App::mp('request');

        if (isset($request->data)) {
            $criteria = $this->formatCriteria($request->data['filter']);
        } elseif (isset($request->get()['request'])) {
            $criteria = $this->decryptToken($request->get()['request']);
        }

        $token = $this->encryptToken($criteria);
        $filter = $this->recoverCriteria($criteria);
        $this->$func($criteria, $token, $filter);
    }

    public function recoverCriteria($criteria = []) {
        if (!empty($criteria['status'])) {
            $criteria['status'] = explode(',', $criteria['status']);
        }

        return $criteria;
    }

    protected function formatCriteria($criteria = []) {
        if (!empty($criteria['status'])) {
            $criteria['status'] = implode(',', $criteria['status']);
        }

        return $criteria;
    }

    protected function makeFilter($criteria = [], $token = '', $filter = '') {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $categories = $this->model()->category();

        $category = '';
        if (isset($criteria['category'])) {
            $category = implode(',', array_keys(App::category()->extract($criteria['category'])));
        }
        if (empty($category)) {
            $category = implode(',', array_keys($categories));
        }

        $where = $alias . '.category_id IN (' . $category . ')';
        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $where .= ' AND ' . $alias . '.status IN (' . $criteria['status'] . ')';
        }

        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.category_id",
            'where' => $where,
            'order' => "{$alias}.id desc",
            'page' => $page,
            'limit' => 10,
        ];
        $data = $this->paginate($option, true);

        $data['category'] = $categories;

        $option = [
            'filter' => [
                'alias' => $alias,
                'category' => App::category()->flat($alias, false, 'title', '&nbsp;&nbsp;&nbsp;'),
                'token' => $token,
                'filter' => $filter
            ]
        ];

        $this->render('search', compact('data', 'option'));
    }

    private function appApi() {
        $model = new \Mp\Model\Apps();

        return $model->api(App::load('login')->targetId());
    }

    private function encryptToken($data = []) {
        $api = $this->appApi();

        $token = "";
        if (empty($data) == false) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $token .= "&{$key}={$value}";
            }
        }

        $security = new Security();
        return $security->encrypt(trim($token, '&'), $api, 2);
    }

    private function decryptToken($params = '') {
        $criteria = [];
        $tmp = explode('token:', $params);
        if (!empty($tmp[1])) {
            $token = $tmp[1];

            $api = $this->appApi();

            $security = new Security();
            $q = $security->decrypt($token, $api, 2);

            $tmp = explode('&', $q);
            $tmp = array_filter($tmp);

            foreach ($tmp as $str) {
                list($key, $value) = explode('=', $str);
                $criteria[$key] = $value;
            }
            return $criteria;
        }
    }
}