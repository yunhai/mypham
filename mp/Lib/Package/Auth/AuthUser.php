<?php

namespace Mp\Lib\Package\Auth;

use Mp\App;

class AuthUser {

    private $target = null;

    public function __construct($target = null) {
        $this->target = $target;
    }

    public function authorize($account = '', $password = '') {
        $info = $this->target->login($account);

        if (empty($info['user']) || !$this->verifyPassword($info['user'], $password)) {
            return [];
        }

        unset($info['user']['password']);

        return $info;
    }

    public function verifyPassword($info = [], $password) {
        return password_verify($password, $info['password']);
    }
}