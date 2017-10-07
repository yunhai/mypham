<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class Gallery extends Model {

    private $detail = null;

    public function __construct($table = 'post', $alias = 'gallery') {
        parent::__construct($table, $alias);
    }

    public function baseCondition() {
        return parent::baseCondition();
    }

    public function detail($detail = null) {
        if (is_null($detail)) {
            return $this->detail;
        }

        return $this->detail = $detail;
    }
}