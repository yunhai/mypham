<?php

use Mp\Core\Validator\Validator;

class StoreValidator extends Validator{

    public function def() {
        return [
            'title' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2901', 'Tên chi nhánh không được trống')
                ]
            ],
            'address' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2902', 'Địa chỉ không được trống')
                ]
            ]
        ];
    }
}
