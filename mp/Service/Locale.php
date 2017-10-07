<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Service\Service;

class Locale extends Service {

    public function __construct() {
        $this->model(new \Mp\Model\Locale());
        $this->candidates = $this->retrieve();
    }

    public function retrieve() {
        $config = App::mp('config');
        $request = App::mp('request');

        $channel = array_search($request->channel, $config->get('app.channel'));
        if (empty($channel)) {
            abort('invalid channel', 500);
        }

        $alias = $this->model()->alias();

        $locale = $request->getLocale();
        if (empty($search)) {
            $locale = $config->get('locale.default');
        }

        if (is_null($locale)) {
            return array();
        }
        $locale = array_search($locale, $config->get('locale.available'));
        if ($locale === false) {
            return array();
        }

        $locale = 'locale_' . $locale;
        $option = [
            'select' => "{$alias}.scope, {$alias}.code, {$alias}.{$locale}",
            'where' => "{$alias}.channel = {$channel}",
            'order' => "{$alias}.code"
        ];

        $tmp = $this->model()->find($option, 'all');
        return Hash::combine($tmp, "{n}.$alias.code", "{n}.$alias.$locale", "{n}.$alias.scope");
    }

    public function __($code = '', $default = '', $scope = '') {
        $scope = $scope ?: 'global';
        if (isset($this->candidates[$scope]) && isset($this->candidates[$scope][$code])) {
            return $this->candidates[$scope][$code];
        }

        return $default ?: $code;
    }
}