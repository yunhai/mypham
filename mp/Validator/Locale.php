<?php

namespace Mp\Validator;
use Mp\Core\Validator\Validator;

class Locale extends Validator {

    public function def() {
        return [
            'code' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Code cannot be blank'),
                ]
            ]
        ];
    }
}