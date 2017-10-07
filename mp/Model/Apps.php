<?php

namespace Mp\Model;

use Mp\Core\Model\Model;

class Apps extends Model {

    public function __construct($table = 'app', $alias = 'app') {
        parent::__construct($table, $alias);
    }

    public function api($id = 0) {
        $select = 'id, api';

        $target = $this->findById($id, $select);

        if ($target) {
            $target = current($target);
            return $target['api'];
        }

        return '';
    }
}