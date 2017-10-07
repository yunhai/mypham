<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class User extends Model {

    public function __construct($table = 'user', $alias = 'user') {
        parent::__construct($table, $alias);
    }

    public function login($account = '', $fields = '') {
        $alias = $this->alias();
        $where = "({$alias}.account = '" . $account . "' OR {$alias}.email = '" . $account . "')";

        return $this->makeLogin($where, $fields);
    }

    public function externalLogin($uid = '', $provider = 'facebook', $fields = '') {
        $alias = $this->alias();
        $where = "{$alias}.uid = '" . $uid . "' AND {$alias}.provider = '" . $provider . "'";

        return $this->makeLogin($where, $fields);
    }

    private function makeLogin($where = '', $fields = '') {
        $request = App::mp('request');

        $current = $request->channel;
        $channel = App::mp('config')->get('app.channel');

        $channel = array_flip($channel);
        $current = intval($channel[$current]);

        $login = App::mp('login');
        $alias = $this->alias();

        $default = [
            'select' => "{$alias}.id, {$alias}.fullname, {$alias}.password",
            'where' => "{$where} AND group.channel = {$current} AND group.status > 0 AND group.deleted is null AND group.app_id = " . $login->targetId(),
            'limit' => 1,
            'join' => [
                [
                    'table' => 'group',
                    'alias' => 'group',
                    'type' => 'INNER',
                    'condition' => $alias . '.group_id = group.id'
                ]
            ]
        ];

        if ($fields) {
            $default['select'] = $fields;
        }

        $info = $this->find($default, 'first');

        if ($info) {
            $info['app'] = [
                'id' => $login->targetId(),
                'code' => $login->targetCode()
            ];
        }

        return $info;
    }

    public function id($id, $fields = '', $channel = 2) {
        $alias = $this->alias();
        $model = App::load('group', 'model');

        $where = "{$alias}.id = {$id} AND group.channel IN ({$channel}) AND " . $model->baseConditionWithAppId();

        $option = [
            'select' => $fields,
            'join' => [
                [
                    'table' => $model->table(),
                    'alias' => $model->alias(),
                    'type' => 'INNER',
                    'condition' => 'group.id = ' . $alias . '.group_id'
                ],
            ],
            'where' => $where,
        ];

        return $this->find($option, 'first');
    }

    public function get($account = '', $fields = '') {
        $request = App::mp('request');

        $channel = App::mp('config')->get('app.channel');
        $channel = array_flip($channel);

        $current = $request->channel;
        $current = intval($channel[$current]);

        $login = App::mp('login');
        $alias = $this->alias();

        $default = [
            'select' => "{$alias}.id, {$alias}.fullname, {$alias}.account, {$alias}.email",
            'where' => "({$alias}.account = '" . $account . "' OR {$alias}.email = '" . $account . "') AND group.channel = {$current} AND group.status > 0 AND group.deleted is null AND group.app_id = " . $login->targetId(),
            'limit' => 1,
            'join' => [
                [
                    'table' => 'group',
                    'alias' => 'group',
                    'type' => 'INNER',
                    'condition' => $alias . '.group_id = group.id'
                ]
            ]
        ];

        return $this->find($default, 'first');
    }
}