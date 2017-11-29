<?php

use Mp\Model\User;

class UserModel extends User
{
    use \Mp\Lib\Traits\Extension;

    public function __construct($table = 'user', $alias = 'user')
    {
        parent::__construct($table, $alias);
        if ($alias == 'user') {
            $this->extension();
        }
    }

    public function login($account = '', $fields = '')
    {
        $alias = $this->alias();
        $fields = "{$alias}.id, {$alias}.fullname, {$alias}.email, {$alias}.password";

        return parent::login($account, $fields);
    }

    public function extension($fields = [])
    {
        $request = Mp\App::mp('request');

        if (!$fields) {
            $fields = $this->field();
        }

        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField($fields);
    }

    public function field()
    {
        return [
            'string_1' => 'address',
            'string_2' => 'phone',
            'string_3' => 'balance'
        ];
    }
}
