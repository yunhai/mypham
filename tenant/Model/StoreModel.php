<?php

use Mp\Model\Post;

class StoreModel extends Post {
    use \Mp\Lib\Traits\Extension;

    public function __construct($model = 'store', $table = 'post', $alias = 'store', $template = '') {
        parent::__construct($model, $table, $alias, $template);
    }
}