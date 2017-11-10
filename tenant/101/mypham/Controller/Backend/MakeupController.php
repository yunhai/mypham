<?php

use Mp\App;

App::uses('Post', 'controller');

class MakeupController extends PostController {
    public function __construct($model = 'makeup', $table = 'post', $alias = 'makeup', $template = 'makeup') {
        parent::__construct($model, $table, $alias, $template);
    }
}