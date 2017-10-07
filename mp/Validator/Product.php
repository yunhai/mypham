<?php

namespace Mp\Validator;

use Mp\Core\Validator\Validator;

class Product extends Validator {

    public function def() {
        return [
            'title' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Title cannot be blank')
                ]
            ],

            'category_id' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2012', 'Category cannot be blank')
                ]
            ]
        ];
    }
}