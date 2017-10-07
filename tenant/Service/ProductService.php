<?php

use Mp\App;
use Mp\Service\Product;
use Mp\Lib\Utility\Hash;

class ProductService extends Product {

    public function __construct($model = 'product', $table = 'product', $alias = 'product') {
        $this->model(App::load($model, 'model', compact('table', 'alias')));
        $this->model()->category(App::category()->flat($alias, false, 'id', '',['where'=> 'status > 0']));
    }

    public function promote($limit = 10) {
        $model = $this->model();
        $alias = $model->alias();

        $query = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id, extension.string_1 as promote",
            'where' => "{$alias}.status >= 1 AND CURDATE() BETWEEN extension.string_4 AND extension.string_5",
            'order' => "{$alias}.id desc",
            'limit' => $limit,
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = ' . $alias . '.id  AND extension.target_model = "' . $alias . '"'
                ],
            ]
        ];

        $result = [];
        $tmp = $model->find($query);
        foreach ($tmp as $id => $item) {
            $result[$id] = array_merge($item['product'], $item['extension']);
        }

        foreach ($result as $key => &$item) {
            $item['discount'] = 0;
            if ($item['price']) {
                $discount = (intval(($item['promote']/$item['price'])*100));
                if ($discount) {
                    $item['discount'] = 100 - $discount;
                }
            }
        }

        $this->associate($result);
        return $result;
    }

    public function hot($limit = 12) {
        $model = $this->model();
        $alias = $model->alias();

        $query = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id, extension.string_1 as promote",
            'where' => "{$alias}.status = 2 AND CURDATE() BETWEEN extension.string_4 AND extension.string_5",
            'order' => "extension.string_4 desc",
            'limit' => $limit,
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = ' . $alias . '.id  AND extension.target_model = "' . $alias . '"'
                ],
            ]
        ];

        $result = [];
        $tmp = $model->find($query);
        foreach ($tmp as $id => $item) {
            $result[$id] = array_merge($item['product'], $item['extension']);
        }

        foreach ($result as $key => &$item) {
            $item['discount'] = 0;
            if ($item['price']) {
                $discount = (intval(($item['promote']/$item['price'])*100));
                if ($discount) {
                    $item['discount'] = 100 - $discount;
                }
            }
        }

        $this->associate($result);
        return $result;
    }

    // public function hot($limit = 12) {
    //     $model = $this->model();

    //     $virtualField = [
    //         'string_1' => 'promote',
    //         'string_4' => 'promote_start',
    //         'string_5' => 'promote_end'
    //     ];
    //     $this->model()->extend($virtualField);

    //     $query = [
    //         'select' => 'id, title, price, file_id, seo_id',
    //         'where' => 'status = 2',
    //         'order' => 'id desc',
    //         'limit' => $limit
    //     ];

    //     $alias = $model->alias();

    //     $result = $model->find($query);
    //     $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");
    //     $today = date('Y-m-d');

    //     foreach ($result as $key => &$item) {
    //         $model->promotion($item);
    //     }

    //     $this->associate($result);
    //     return $result;
    // }

    public function store($limit = 8) {
        $model = $this->model();

        $virtualField = [
            'string_1' => 'promote',
            'string_4' => 'promote_start',
            'string_5' => 'promote_end'
        ];
        $this->model()->extend($virtualField);

        $query = [
            'select' => 'id, title, price, file_id, seo_id',
            'where' => 'status = 3',
            'order' => 'id desc',
            'limit' => $limit
        ];

        $alias = $model->alias();

        $result = $model->find($query);
        $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");

        $today = date('Y-m-d');
        foreach ($result as $key => &$item) {
            $model->promotion($item);
        }

        $this->associate($result);
        return $result;
    }
}