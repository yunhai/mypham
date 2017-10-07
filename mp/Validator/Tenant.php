<?php

namespace Mp\Validator;
use Mp\Core\Validator\Validator;
use Mp\App;

class Tenant extends Validator {

    public function def() {
        return [
            'email' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('1001', 'Email không được trống'),
                ],
                'email' => [
                    'rule' => ['email'],
                    'message' => __('1002', 'Email không hợp lệ'),
                ]
            ]
        ];
    }
}