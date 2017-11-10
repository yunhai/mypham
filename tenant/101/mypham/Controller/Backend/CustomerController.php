<?php

use Mp\App;

App::uses('Post', 'controller');

class CustomerController extends PostController {
    public function __construct($model = 'customer', $table = 'post', $alias = 'customer', $template = 'customer') {
        parent::__construct($model, $table, $alias, $template);
    }
}