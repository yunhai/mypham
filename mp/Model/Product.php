<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;
use Mp\Lib\Utility\Hash;

class Product extends Model {


    protected $category = [];

    public function __construct($table = 'product', $alias = 'product') {
        parent::__construct($table, $alias);
    }

    public function category($category = null) {
        if (is_null($category)) {
            return $this->category;
        }

        return $this->category = $category;
    }

    public function baseCondition() {
        $alias = $this->alias();
        return $alias . '.category_id IN (' . implode(',', array_keys($this->category)) . ') AND ' . parent::baseCondition();
    }
}