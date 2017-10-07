<?php

namespace Mp\Controller\Frontend;

use Mp\App;
use Mp\Core\Controller\Frontend;

class Page extends Frontend {

    public function __construct($model = 'page', $table = 'page', $alias = 'page', $template = '') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'detail':
                 $this->detail($request->query[2]);
                break;
            default :
                $this->index();
                break;
        }
    }

    public function detail($id = 0) {
        $alias = $this->model()->alias();

        $select = "{$alias}.id, {$alias}.title, {$alias}.content";
        $where = "{$alias}.id = {$id} AND {$alias}.status > 0";

        $target = $this->model()->find(compact('select', 'where'), 'first');
        if (empty($target)) {
           abort('NotFoundException');
        }

        $target = $target[$alias];
        $breadcrumb = [
            'target' => $target
        ];
        $this->set('breadcrumb', $breadcrumb);
        $this->render('detail', compact('target'));
    }
}