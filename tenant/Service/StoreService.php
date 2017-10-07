<?php

use Mp\App;
use Mp\Service\Post;

class StoreService extends Post {
    public function __construct($model = 'store', $table = 'post', $alias = 'store') {
        $this->model(App::load($model, 'model', compact('table', 'alias')));
        $this->model()->category(App::category()->flat('store'));
    }

    public function getById($id = 0) {
        $extend = [
            'string_1' => 'address',
            'string_2' => 'phone',
            'string_5' => 'logo',
            'text_1' => [
                'email',
                'summary'
            ]
        ];

        $option = [
            'select' => "id, title, category_id",
            'where' => "id = " . $id,
            'limit' => 1
        ];

        return parent::get($option, $extend);
    }
}
