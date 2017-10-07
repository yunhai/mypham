<?php

namespace Mp\Model;

use Mp\Core\Model\Model;

class Tenant extends Model {

    public function __construct() {
        parent::__construct('tenant', 'tenant');
    }

    public function isExist($target) {
        $option = [
            'select' => "tenant.id",
            'where' => "tenant.email = '{$target}'",
            'limit' => 1,
        ];

        return empty($this->find($option, 'list')) == false;
    }
}