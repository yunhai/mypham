<?php

use Mp\Controller\Backend\Menu;

class MenuController extends Menu {
    public function __construct($model = 'menu', $table = 'menu', $alias = 'menu', $template = '') {
        parent::__construct($model, $table, $alias, $template);
    }
}