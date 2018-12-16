<?php

use Mp\App;

App::uses('Post', 'controller');

class PromotionController extends PostController {
    public function __construct($model = 'promotion', $table = 'post', $alias = 'promotion', $template = 'promotion') {
        parent::__construct($model, $table, $alias, $template);
    }
}