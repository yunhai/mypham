<?php

use Mp\App;

App::uses('Post', 'controller');

class AdvisoryController extends PostController {

    public function __construct($model = 'advisory', $table = 'post', $alias = 'advisory', $template = 'advisory') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function index() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.created, {$alias}.modified, {$alias}.category_id",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);
        $data['category'] = $this->model()->category();

        $option = [
            'filter' => [
                'alias' => $alias,
                'category' => App::category()->flat($alias, false, 'title', '&nbsp;&nbsp;&nbsp;')
            ]
        ];

        $this->render('index', compact('data', 'option'));
    }
}