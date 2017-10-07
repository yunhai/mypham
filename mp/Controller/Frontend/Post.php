<?php

namespace Mp\Controller\Frontend;

use Mp\App;
use Mp\Core\Controller\Frontend;

class Post extends Frontend {

    public function __construct($model = 'post', $table = 'post', $alias = 'post', $template = '') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'category':
                $this->category();
                break;
            case 'detail':
                 $this->detail($request->query[2]);
                break;
            default :
                $this->index();
                break;
        }
    }

    public function index() {
        $request = App::mp('request');

        $list = $data = [];
        $alias = $this->model()->alias();

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.idx, {$alias}.modified, {$alias}.category_id",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];
        $data = $this->paginate($option, true);
        $data['category'] = $this->model()->category();

        $this->render('index', compact('data'));
    }
}