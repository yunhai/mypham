<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Controller\Backend\User;

class Admin extends User {
    public function __construct($model = 'user', $table = 'user', $alias = 'admin', $template = 'admin') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function add($channel = 2) {
        parent::add($channel);
    }

    public function edit($id = 0, $channel = 2) {
        parent::edit($id, $channel);
    }

    public function updatePassword($channel = 2) {
        parent::updatePassword($channel);
    }

    protected function groups($channel = 2) {
        return parent::groups($channel);
    }

    public function index($channel = 2) {
        parent::index($channel);
    }

    protected function makeFilter($criteria = [], $token = '', $filter = '', $channel = 2) {
        parent::makeFilter($criteria, $token, $filter, $channel);
    }
}
