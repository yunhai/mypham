<?php

namespace Mp\Lib\Helper;
use Mp\App;

class Html {

    public function media() {
        return App::mp('config')->get('app.url.media') .
               App::mp('login')->targetCode();
    }

    public function asset() {
        return App::mp('config')->get('app.url.asset') .
               App::mp('login')->targetCode() . '/' .
               App::mp('config')->get('app.view.' . App::mp('request')->channel);
    }

    public function img() {
        return self::asset() . '/img';
    }

    public function js($list = [], $path = '') {
        if (empty($path)) {
            $path = 'js';
        }

        $path = self::asset() . '/' . $path . '/';

        $result = '';
        foreach ($list as $item) {
            $src = $path . $item . '.js';

            $result .= '<script src="' . $src . '"></script>';
        }

        return $result;
    }

    public function css($list = [], $path = '') {
        if (empty($path)) {
            $path = 'css';
        }

        $path = self::asset() . '/' . $path . '/';

        $result = '';
        foreach ($list as $item) {
            $src = $path . $item . '.css';
            $result .= '<link rel="stylesheet" type="text/css" rel="stylesheet" href="' . $src . '" />';
        }

        return $result;
    }
}