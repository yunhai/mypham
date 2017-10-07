<?php

namespace Mp\Core\Controller;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Controller\Controller;

class Frontend extends Controller {

    public function __construct($model, $table = '', $alias = '', $template = '') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'category':
                $this->category($request->query[2]);
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
    }

    public function detail($id = 0) {
        $alias = $this->model()->alias();

        $select = "{$alias}.id, {$alias}.title, {$alias}.content, {$alias}.modified, {$alias}.category_id, {$alias}.file_id";
        $where = "{$alias}.id = {$id} AND {$alias}.status > 0";

        $target = $this->model()->find(compact('select', 'where'), 'first');
        if (empty($target)) {
           abort('NotFoundException');
        }

        $target = $target[$alias];
        $others = $this->other($target);

        $this->render('detail', compact('target', 'others'));
    }

    protected function other($target, $option = []) {
        $alias = $this->model()->alias();

        $id = $target['id'];
        $category = $target['category_id'];

        $service = App::category();
        $tmp = $service->extract($category);

        if (empty($tmp) === false) {
            $category = implode(',', array_keys($tmp));
        }

        $association = [
            'seo' => 'id, alias'
        ];

        $select = "{$alias}.id, {$alias}.title, {$alias}.seo_id";
        $where = "{$alias}.id < {$id} AND {$alias}.status > 0 AND {$alias}.category_id IN ({$category})";
        $order = "{$alias}.id desc";
        $limit = 5;
        extract($option);

        $others = $this->model()->find(compact('select', 'where', 'limit', 'order'));
        $others = Hash::combine($others, '{n}.' . $alias . '.id', '{n}.' . $alias);
        return $this->model()->associate($others, $association);
    }

    public function isAjax() {
        $request = App::mp('request');
        return !empty($request->data['ajax']);
    }
}