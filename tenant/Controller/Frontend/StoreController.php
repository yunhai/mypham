<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Controller\Frontend;

class StoreController extends Frontend {

    public function __construct($model = 'store', $table = 'post', $alias = 'store', $template = '') {
        parent::__construct($model, $table, $alias, $template);

        $this->model()->category(App::category()->flat('store'));

        $virtualField = [
            'string_1' => 'address',
            'string_2' => 'phone',
            'string_3' => 'product_category',
            'string_4' => 'product_amout',
            'string_5' => 'logo',
            'text_1' => [
                'email',
                'location', //latitude, longitude
                'summary'
            ]
        ];

        $this->model()->loadExtension(new \Mp\Model\Extension());
        $this->model()->virtualField($virtualField);
    }

    public function index() {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => 'id, title, seo_id',
            'where' => 'status > 0',
            'order' => '`status` desc, idx desc, id desc',
            'limit' => 12,
            'page' => $page,
            'paginator' => ['navigator' => false]
        ];

        $page = [];
        $data = $this->paginate($option, true, $page);
        $data['list'] = Hash::combine($data['list'], '{n}.store.id', '{n}.store');
        $this->associate($data['list'], ['seo' => 'seo_id', 'file' => 'logo']);

        if ($this->isAjax()) {
            $items = $data['list'];
            $data = [
                'total' => $page['total'],
                'current' => $page['current'],
                'html' => $this->render('item', compact('items')),
            ];
            return $this->renderJson($data);
        }

        $current_url = App::load('url')->current();
        $this->render('index', compact('data', 'page', 'current_url'));
    }

    public function detail($id = 0) {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $select = "{$alias}.id, {$alias}.title, {$alias}.content, {$alias}.modified";
        $where = "{$alias}.id = {$id} AND {$alias}.status > 0";

        $target = $this->model()->find(compact('select', 'where'), 'first');
        if (empty($target)) {
           abort('NotFoundException');
        }

        $target = $target[$alias];
        $others = $this->other($target);

        $this->render('detail', compact('target', 'others'));
    }

    public function other($target, $option = []) {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = [];

        $option = [
            'select' => 'id, title',
            'where' => "id < {$target['id']} AND status > 0",
            'order' => '`status` desc, idx desc, id desc',
            'limit' => 9,
        ];

        $list = $this->model()->find($option);

        $list = Hash::combine($list, '{n}.store.id', '{n}.store');
        $this->associate($list, ['seo' => 'seo_id', 'file' => 'logo']);

        return $list;
    }
}