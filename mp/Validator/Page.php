<?php

namespace Mp\Validator;

use Mp\App;
use Mp\Core\Validator\Validator;

class Page extends Validator {

    public function def($scope = '') {
        return [
            'title' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Tiêu đề không được trống', $scope)
                ]
            ],
            'section' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Mã trang không được trống', $scope)
                ],
                'uniqueCode' => [
                    'rule' => ['uniqueSection'],
                    'message' => __('2011', 'Mã trang đã được sử dụng', $scope),
                ]
            ]
        ];
    }

    public function uniqueSection($checked = '', $data = []) {
        $model = App::load('page', 'model');

        $id = empty($data['id']) ? 0 : $data['id'];
        $option = [
            'select' => 'id',
            'where' => 'section = "' . $checked .'" AND id <> ' . $id,
            'limit' => 1,
        ];

        return empty($model->find($option, 'first'));
    }
}