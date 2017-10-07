<?php

namespace Mp\Lib\Helper;

use Mp\App;

class Url {

    public function current() {
        $request = App::mp('request');

        $url = $request->branchUrl();
        return $url . $request->param['request'];
    }

    public function get($params = []) {
        $request = App::mp('request');

        $url = $request->branchUrl();

        foreach ($request->query as $k => $v) {
            if ($k === 'action' || $k === 'module') {
                continue;
            }

            if (empty($v) && !isset($params[$k])) {
                continue;
            }

            if (strpos($v, ':') !== false) {
                list($k, $v) = explode(':', $v);
            }

            if (isset($params[$k])) {
                $url .= $k . ':' . $params[$k] . '/';
                unset($params[$k]);
            } else {
                $url .= $v . '/';
            }
        }

        foreach ($params as $k => $v) {
            $url .= $k . ':' . $params[$k] . '/';
        }

        return rtrim($url);
    }

    public function module($url = '') {
        $request = App::mp('request');

        return $request->branchUrl() . $request->query['module'] . '/' . $url;
    }

    public function full($url = '') {
        $request = App::mp('request');

        return trim($request->branchUrl() . $url, '/');
    }

    public function real($module = '', $type = '', $target = '') {
        $tmp = $module . '/' . $type;
        if (isset($target['id'])) {
            $tmp .= '/' . $target['id'];
        }
        return $tmp;
    }
}