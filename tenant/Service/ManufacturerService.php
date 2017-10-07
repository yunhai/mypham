<?php

use Mp\App;
use Mp\Service\Post;

class ManufacturerService extends Post {
    public function __construct($model = 'manufacturer', $table = 'post', $alias = 'manufacturer') {
        $this->model(App::load($model, 'model', compact('table', 'alias')));
        $this->model()->category(App::category()->flat('manufacturer'));
    }

    public function getById($id = 0) {
        $extend = [
            'string_1' => 'origin',
        ];

        $option = [
            'select' => "id, title",
            'where' => "id = " . $id . ' AND status = 1',
            'limit' => 1
        ];

        return parent::get($option, $extend);
    }

    public function all()
    {
        $extend = [
        ];

        $option = [
            'select' => "id, title, file_id",
            'where' => "status = 1"
        ];

        return parent::get($option, $extend);
    }
}
