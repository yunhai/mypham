<?php

namespace Mp\Validator;

use Mp\Core\Validator\Validator;

class MailRecipient extends Validator {

    public function def() {
        return [
            'email' => [
                'format' => [
                    'rule' => ['email'],
                    'message' => __('5009', 'Email không hợp lệ'),
                ],
                'uniqueEmail' => [
                    'rule' => ['uniqueEmail'],
                    'message' => __('5004', 'Tài khoản đã được đăng ký'),
                ]
            ]
        ];
    }

    public function uniqueEmail($checked = '', $data = []) {
        $model = new \Mp\Model\MailRecipient();
        // $appId = App::mp('login')->targetId();

        $id = empty($data['id']) ? 0 : $data['id'];
        $option = [
            'select' => 'id',
            'where' => 'email = "' . $checked .'" AND id <> ' . $id,
            'limit' => 1,
        ];

        return empty($model->find($option, 'first'));
    }
}