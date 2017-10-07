<?php

namespace Mp\Lib\Package\Auth;

use Mp\App;
use Mp\Lib\Session;

class Auth {

    private $method = null;
    public function __construct($method = null) {
        $this->method = $method;
    }

    public function login($account = '', $password = '') {
        return $this->authorize($account, $password);
    }

    public function logout() {
        $key = App::mp('login')->targetId() . '.' . App::mp('request')->channel;
        return Session::delete('auth.' . $key);
    }

    private function authorize($account = '', $password = '') {
        $info = $this->method->authorize($account, $password);

        if (empty($info)) {
            return false;
        }

        return $this->storeLoginInfo($info);
    }

    public function storeLoginInfo($info = []) {
        $info['locale'] = Session::read('target.locale');
        $key = App::mp('login')->targetId() . '.' . App::mp('request')->channel;

        return Session::write('auth.' . $key, $info);
    }

    static public function authenticate() {
        $request = App::mp('request');
        $config = App::mp('config');
        $login = App::mp('login');

        $channel = $request->channel;

        if (in_array($channel, $config->get('authorize.check'))) {
            $ignore = $config->get('authorize.allow.' . $channel);

            if (!empty($ignore)) {
                if (isset($ignore[$request->query['module']])) {
                    if (empty($ignore[$request->query['module']])) {
                        return true;
                    }

                    if (in_array($request->query['action'], $ignore[$request->query['module']])) {
                        return true;
                    }
                }
            }

            return !empty($login->userId());
        }

        if (!empty($config->get('authorize.modular.' . $channel)) &&
            array_key_exists($request->query['module'], $config->get('authorize.modular.' . $channel))) {
            $list = $config->get('authorize.modular.' . $channel . '.' . $request->query['module']);
            if (in_array('full', $list) || in_array($request->query['action'], $list)) {
                return !empty($login->userId());
            }
        }

        return true;
    }
}