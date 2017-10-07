<?php

namespace Mp\Lib;

use Mp\App;
use Mp\Lib\Utility\Hash;

class Session {

    static public function check($name = '') {
        if (empty($name)) {
            return false;
        }

        return Hash::get($_SESSION, $name) !== null;
    }

    static public function write($name, $value = null) {
        if (empty($name)) {
            return false;
        }

        $write = $name;
        if (!is_array($name)) {
            $write = array($name => $value);
        }

        foreach ($write as $key => $val) {
            self::_overwrite($_SESSION, Hash::insert($_SESSION, $key, $val));
            if (Hash::get($_SESSION, $key) !== $val) {
                return false;
            }
        }

        return true;
    }

    static public function read($name = '') {
        if (empty($name) && $name !== null) {
            return null;
        }

        if ($name === null) {
            return $_SESSION;
        }

        $result = Hash::get($_SESSION, $name);

        if (isset($result)) {
            return $result;
        }

        return null;
    }

    static public function delete($name = '') {
        if (self::check($name)) {
            self::_overwrite($_SESSION, Hash::remove($_SESSION, $name));
            return !self::check($name);
        }

        return false;
    }

    static public function consume($name) {
        if (empty($name)) {
            return null;
        }
        $value = static::read($name);
        if ($value !== null) {
            static::_overwrite($_SESSION, Hash::remove($_SESSION, $name));
        }
        return $value;
    }

    static protected function _overwrite(&$old, $new) {
        if (!empty($old)) {
            foreach ($old as $key => $var) {
                if (!isset($new[$key])) {
                    unset($old[$key]);
                }
            }
        }

        foreach ($new as $key => $var) {
            $old[$key] = $var;
        }
    }

    static public function destroy() {
        session_destroy();

        $_SESSION = null;
    }
}