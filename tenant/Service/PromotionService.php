<?php

use Mp\App;
use Mp\Service\Post;

class PromotionService extends Post {
    public function __construct($model = 'promotion', $table = 'post', $alias = 'promotion') {
        $this->model(App::load($model, 'model', compact('table', 'alias')));
        $this->model()->category(App::category()->flat($alias, false, 'title', '', ['where' => 'status > 0']));
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
        $extend = [];

        $option = [
            'select' => "id, title, file_id",
            'where' => "status = 1"
        ];

        return parent::get($option, $extend);
    }

    public function first($option = [], $extend = [], $association = [])
    {
        $option = [
            'select' => "id, title, file_id, content",
            'where' => "status = 1",
            'order_by' => 'id desc',
            'limit' => 1
        ];

        return parent::get($option, $extend);
    }
}
