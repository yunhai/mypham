<?php

namespace Mp\Service;

use Mp\Core\Service\Service;

class Apps extends Service {

    public function __construct($table = 'app', $alias = 'app') {
        $this->model(new \Mp\Model\Apps($table, $alias));
    }

    public function getByDomain($domain = '') {
        $domain = env('REQUEST_SCHEME') . "://" . $domain;
        $alias = $this->model()->alias();

        $option = [
            'select' => "{$alias}.id, {$alias}.code, {$alias}.domain, {$alias}.tenant_id"
        ];

        $where = "{$alias}.status > 0 AND ";
        $fields = [
            'domain' => "{$alias}.domain='" . $domain . "'",
            'alternate_domain' => "{$alias}.alternate_domain LIKE '%;" . $domain . ";%'"
        ];

        foreach ($fields as $field => $condition) {
            $option['where'] = $where . $condition;
            $target = $this->model()->find($option, 'first');
            if ($target) {
                return $target;
            }
        }
        return [];
    }

    public function instance($code = '') {
        $alias = $this->model()->alias();

        $option = [
            'select' => "{$alias}.id, {$alias}.name, {$alias}.code, {$alias}.tenant_id",
            'where' => "{$alias}.status > 0 AND {$alias}.code='" . $code . "'",
        ];

        return $this->model()->find($option, 'first');
    }
}
