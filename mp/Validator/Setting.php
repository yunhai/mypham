<?php

namespace Mp\Validator;

use Mp\App;
use Mp\Core\Validator\Validator;

class Setting extends Validator {

    public function def() {
        return [
            'key' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Key cannot be blank'),
                ],
                'unique' => [
                    'rule' => ['keyUnique'],
                    'message' => __('2011', 'Key must be unique'),
                ]
            ]
        ];
    }

    public function keyUnique($checked = '', $data = []) {
        $model = App::load('setting', 'model');

        $appId = App::mp('login')->appId();
        $option = [
            'select' => 'setting.id',
            'where' => 'setting.key = "' . $checked . '"',
            'limit' => 1,
        ];

        return empty($model->find($option, 'first'));
    }
}