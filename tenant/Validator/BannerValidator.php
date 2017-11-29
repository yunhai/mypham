<?php

use Mp\Validator\Page;

class BannerValidator extends Page {
    public function def($scope = '') {
        return [
            'title' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Tiêu đề không được trống')
                ]
            ],
            'section' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Loại banner không được trống')
                ],
                'available' => [
                    'rule' => ['available'],
                    'message' => __('2011', 'Loại banner không chính xác'),
                ]
            ]
        ];
    }

    public function available($checked = '', $data = []) {
        $section = [
            'home' => 'Trang chủ',
            'header' => 'Header trang chủ',
            'right' => 'Bên phải',
            'product' => 'Sản phẩm'
        ];

        return isset($section[$checked]);
    }
}
