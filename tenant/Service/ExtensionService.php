<?php

use Mp\App;
use Mp\Service\Extension;

class ExtensionService extends Extension {
    public function __construct($model = '', $table = '', $alias = '') {
        if ($model) {
            $this->model = App::load($model, 'model', compact('table', 'alias'));
        }
    }
}
