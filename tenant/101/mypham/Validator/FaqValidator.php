<?php

use Mp\Core\Validator\Validator;

class FaqValidator extends Validator {

    public function def() {
        return [
            'question' => [
                'notEmpty' => [
                    'rule' => ['notEmpty'],
                    'message' => __('2011', 'Câu hỏi không được trống')
                ]
            ]
        ];
    }
}
