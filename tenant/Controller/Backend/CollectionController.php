<?php

use Mp\App;

App::uses('Post', 'controller');

class CollectionController extends PostController {
    public function __construct($model = 'collection', $table = 'post', $alias = 'collection', $template = 'collection') {
        parent::__construct($model, $table, $alias, $template);
    }
}