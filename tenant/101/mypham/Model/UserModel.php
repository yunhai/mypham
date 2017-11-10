<?php

use Mp\Model\User;

class UserModel extends User {

    use \Mp\Lib\Traits\Extension;

    public function login($account = '', $fields = '') {
        $alias = $this->alias();
        $fields = "{$alias}.id, {$alias}.fullname, {$alias}.email, {$alias}.password";
        return parent::login($account, $fields);
    }

    public function extension($fields = []) {
        $request = Mp\App::mp('request');
        if ($request->channel == 'backend') {
            return true;
        }

        if (!$fields) {
            $fields = [
                'string_1' => 'address',
                'string_2' => 'phone',
                'string_3' => 'balance',
                'text_1' => [
                    'birthday',
                    'gender',
                    'subcribe'
                ]
            ];
        }

        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField($fields);
    }
}