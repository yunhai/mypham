<?php

namespace Mp\Validator;

use Mp\Core\Validator\Validator;

class Post extends Validator
{
    public function def()
    {
        return [
            'title' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2901', 'Title cannot be blank')
                ]
            ]
        ];
    }
}
