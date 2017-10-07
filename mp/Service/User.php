<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Package\Auth\Auth;
use Mp\Core\Service\Service;
use Mp\Lib\Helper\Security;

class User extends Service {

    public function __construct($model = null) {
        parent::__construct();
        $this->model($model);
    }

    public function encryptPassword($password) {
        $security = new Security();
        return $security->hash($password);
    }

    public function updatePassword($data = [], &$error = [], $validator = true, $rule = 'updatePassword') {
        $alias = $this->model()->alias();

        if ($validator) {
            $flag = $this->validate($alias, $data, $error, 1, $rule);
            if (!$flag) {
                return false;
            }
        }
        $data['password'] = $this->encryptPassword($data['password']);

        return $this->model()->save($data);
    }

    public function login($account, $password, $method = 'local') {
        switch ($method) {
            default:
                $authMethod = new \Mp\Lib\Package\Auth\AuthUser($this->model());
                break;
        }

        $auth = new Auth($authMethod);
        return $auth->login($account, $password);
    }

    public function logout() {
        $auth = new Auth();
        return $auth->logout();
    }
}