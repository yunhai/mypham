<?php

namespace Mp\Lib\Helper;

use Mp\App;
use Mp\Lib\Utility\Text;

class Format {

    public function truncate($text = '', $length = 100, $option = []) {
        $defaults = array(
            'exact' => false,
            'html' => false,
            'img' => false,
            'alt' => ''
        );

        $defaults = array_merge($defaults, $option);
        return Text::truncate($text, $length, $defaults);
    }

    public function link($target = [], $module = '', $type = 'detail', $param = '') {
        $map = App::mp('view')->reference('seo');

        if (isset($target['seo_id']) && array_key_exists($target['seo_id'], $map)) {
            $string = $map[$target['seo_id']]['alias'];
        } else {
            $url = new Url();
            $string = $url->real($module, $type, $target);
        }

        return App::mp('request')->branchUrl() . $string . $param;
    }

    public function url($string = '', $param = '') {
        $map = App::mp('view')->reference('url');
        $string = isset($map[$string]) ? $map[$string] : $string;

        if ($param) {
            $param = '/' . $param;
        }

        return App::mp('request')->branchUrl() . $string . $param;
    }
}