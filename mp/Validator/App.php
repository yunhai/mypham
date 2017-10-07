<?php

namespace Mp\Validator;
use Mp\Core\Validator\Validator;

class App extends Validator {

    public function def() {
        return [
            'domain' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('5002', 'Địa chỉ website không được trống'),
                ],
                'domain' => [
                    'rule' => ['domain'],
                    'message' => __('5003', 'Địa chỉ website không hợp lệ'),
                ],
                'available' => [
                    'rule' => ['available'],
                    'message' => __('5004', 'Địa chỉ website đã được đăng ký'),
                ]
            ]
        ];
    }

    public function available($checked) {
        $model = new Mp\Model\Apps('app', 'app');
        $option = [
            'select' => 'id',
            'where' => 'domain = "' . $checked . '" AND status > 0',
            'limit' => 1,
        ];

        return empty($model->find($option, 'first'));
    }

    public function domain($checked = '') {
        return filter_var($checked, FILTER_VALIDATE_URL);
    }
}