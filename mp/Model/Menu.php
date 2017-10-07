<?php

namespace Mp\Model;
use Mp\Model\Category;

class Menu extends Category {

    public function __construct($table = 'menu', $alias = 'menu') {
        parent::__construct($table, $alias);
    }
}