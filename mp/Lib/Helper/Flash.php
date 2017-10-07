<?php

namespace Mp\Lib\Helper;

use Mp\App;
use Mp\Lib\Session;

class Flash {

    public function set($name = '', $message = null, $group = null) {
        $token = 'auth.' . App::mp('login')->targetId() . '.' . App::mp('request')->channel . '.flash';

        if (is_null($group)) {
            $token .= '.' . $name;
            Session::write($token, $message);
        } else {
            $token .= '.' . $group;
            $session = Session::read($token);
            $session[$name] = $message;
            Session::write($token, $session);
        }

        return true;
    }

    public function get($name = null) {
        $token = 'auth.' . App::mp('login')->targetId() . '.' . App::mp('request')->channel . '.flash';

        if ($name) {
            $token .= '.' . $name;
        }

        return Session::consume($token);
    }
}