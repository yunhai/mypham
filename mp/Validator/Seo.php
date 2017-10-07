<?php

namespace Mp\Validator;

use Mp\App;
use Mp\Core\Validator\Validator;

class Seo extends Validator {

    public function def() {
        return [
            'alias' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Url không được trống')
                ],
                'unique'  => [
                    'rule' => ['uniqueAlias'],
                    'message' => __('2011', 'Url đã được sử dụng')
                ]
            ]
        ];
    }

    public function uniqueAlias($check, $data = []) {
        $option = [
            'select' => "id",
            'where' => "alias = '" . trim($check) . "' AND id <>" . intval($data['id']),
            'limit' => 1
        ];

        $tmp = App::load('seo', 'model')->find($option, 'first');
        return empty($tmp);
    }
}