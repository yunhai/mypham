<?php

use Mp\App;
use Mp\Core\Controller\Frontend;

class RepairController extends Frontend {

    public function __construct($model = '', $table = '', $alias = '', $template = '') {
        parent::__construct(null, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');
        $helper = App::mp('config');

        set_time_limit (6000);

        switch ($request->query['action']) {
            default:
                $this->repair();
                break;
        }
    }

    public function repair() {
        $this->post_file();
        $this->post_seo();
        $this->product_file();
        $this->product_seo();


        $model = App::load('post', 'model');


        print "<pre>";
        print_r ($model->getQueries());
        print "</pre>";
        exit;
    }

    private function post_file() {
        $model = App::load('file', 'model');
        $option = [
            'select' => 'target_id, id',
            'where' => 'target_model <> "product"',
            'order' => 'id'
        ];
        $files = $model->find($option, 'list');

        $model = App::load('post', 'model');

        $option = [
            'select' => 'id, id',
            'where' => 'file_id = 0',
            'order' => 'id desc'
        ];
        $posts = $model->find($option, 'list');

        $error = [];
        foreach($posts as $target => $id) {
            if (isset($files[$id])) {
                $fields = [
                    'file_id' => $files[$id]
                ];

                $condition = 'id IN (' . $target . ') AND file_id = 0';
                $model->modify($fields, $condition);
            } else {
                $error[] = $id;
            }
        }
        App::log(print_r($error, true), 'post_file');
    }

    public function post_seo() {
        $model = App::load('seo', 'model');
        $option = [
            'select' => 'target_id, id',
            'where' => 'target_model <> "product"',
            'order' => 'id'
        ];
        $seo = $model->find($option, 'list');

        $model = App::load('post', 'model');

        $option = [
            'select' => 'id, id',
            'where' => 'seo_id = 0',
            'order' => 'id desc'
        ];
        $posts = $model->find($option, 'list');

        $error = [];
        foreach($posts as $target => $id) {
            if (isset($seo[$id])) {
                $fields = [
                    'seo_id' => $seo[$id]
                ];

                $condition = 'id IN (' . $target . ')';
                $model->modify($fields, $condition);
            } else {
                $error[] = $id;
            }
        }
        App::log(print_r($error, true), 'post_seo');
    }

    private function product_file() {
        $model = App::load('file', 'model');
        $option = [
            'select' => 'target_id, id',
            'where' => 'target_model = "product"',
            'order' => 'id'
        ];
        $files = $model->find($option, 'list');

        $model = App::load('product', 'model');

        $option = [
            'select' => 'id, id',
            'where' => 'file_id = 0',
            'order' => 'id'
        ];
        $products = $model->find($option, 'list');

        foreach($products as $target => $id) {
            $fields = [
                'file_id' => $files[$id]
            ];

            $condition = 'id IN (' . $target . ') AND file_id = 0';
            $model->modify($fields, $condition);
        }
    }

    public function product_seo() {
        $model = App::load('seo', 'model');
        $option = [
            'select' => 'target_id, id',
            'where' => 'target_model = "product"',
            'order' => 'id'
        ];
        $seo = $model->find($option, 'list');

        $model = App::load('product', 'model');

        $option = [
            'select' => 'id, id',
            'where' => 'seo_id = 0',
            'order' => 'id desc'
        ];
        $products = $model->find($option, 'list');

        foreach($products as $target => $id) {
            $fields = [
                'seo_id' => $seo[$id]
            ];

            $condition = 'id IN (' . $target . ')';
            $model->modify($fields, $condition);
        }
    }


    public function index() {
        $categoryService = App::category();

        $service = App::load('product', 'service');
        $hot = $service->hot();
        $this->associate($hot);

        $store = $service->store();
        $this->associate($store);

        $promote = $service->promote();
        $this->associate($promote);

        $service = App::load('makeup', 'service');
        $news = $service->lastest();
        $this->associate($news);

        $service->model()->category($categoryService->flat('advisory'));
        $advisory = $service->lastest();
        $this->associate($advisory);

        $service->model()->category($categoryService->flat('collection'));
        $collection = $service->lastest();
        $this->associate($collection);

        $service = App::load('post', 'service', ['store', 'post', 'store']);
        $service->category('store');

        $option = [
            'select' => 'id, title, seo_id',
            'where' => 'status > 0',
            'order' => '`status` desc, idx desc, id desc',
            'limit' => 15
        ];
        $extend = [
            'string_1' => 'address',
            'string_2' => 'phone',
            'string_5' => 'file_id',
            'text_1' => [
                'email',
                'location'
            ]
        ];

        $shop = $service->get($option, $extend);
        $this->associate($shop);

        $this->render('index', compact('hot', 'store', 'news', 'promote', 'advisory', 'collection', 'shop'));
    }
}