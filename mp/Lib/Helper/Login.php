<?php

namespace Mp\Lib\Helper;

use Mp\App;
use Mp\Lib\Session;

class Login {

    public function loggedIn() {
        return !empty(self::userId());
    }

    public function tenantId() {
        $key = self::targetId() . '.' . App::mp('request')->channel;
        return Session::read('auth.' . $key . '.tenant.id');
    }

    public function user() {
        $key = self::targetId() . '.' . App::mp('request')->channel;
        return Session::read('auth.' . $key . '.user');
    }

    public function userId() {
        $key = self::targetId() . '.' . App::mp('request')->channel;
        return Session::read('auth.' . $key . '.user.id');
    }

    public function appId() {
        $key = self::targetId() . '.' . App::mp('request')->channel;
        return Session::read('auth.' . $key . '.app.id');
    }

    public function auth($info = null) {
        $key = 'auth.' . self::targetId() . '.' . App::mp('request')->channel;
        if ($info) {
            $key .= '.' . $info;
        }

        return Session::read($key);
    }

    public function target($key = null) {
        if (empty($key)) {
            return Session::read("target");
        }

        return Session::read("target.{$key}");
    }

    public function targetLocale() {
        return Session::read("target.locale");
    }

    public function targetCode() {
        return Session::read("target.app.code");
    }

    public function targetId() {
        return Session::read("target.app.id");
    }

    public function locale() {
        return Session::read("locale");
    }

    public function flash($name = '', $group = null) {
        return App::load('flash')->get($name, $group);
    }
}