<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Core\Controller\Backend;

class Extension extends Backend {

    public function __construct($model = '', $table = '', $alias = '', $template = '') {
        parent::__construct($model, $table, $alias, $template);
    }

}