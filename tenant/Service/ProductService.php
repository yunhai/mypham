<?php

use Mp\App;
use Mp\Service\Product;
use Mp\Lib\Utility\Hash;

class ProductService extends Product
{
    public function __construct($model = 'product', $table = 'product', $alias = 'product')
    {
        $this->model(App::load($model, 'model', compact('table', 'alias')));
        $this->model()->category(App::category()->flat($alias, false, 'id', '', ['where' => 'status > 0']));
    }

    public function get($option = [], $extend = [], $association = [])
    {
        $alias = $this->model()->alias();

        $default = [
            'select' => "{$alias}.id, {$alias}.title",
        ];

        $default = array_merge($default, $option);

        if ($extend) {
            $this->model()->extend($extend);
        }

        $result = $this->model()->find($default);

        $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");

        if ($extend) {
            $this->associate($result);
        }
        $this->model()->checkPromotion($result);

        if ($association) {
            return $this->model()->associate($result, $association);
        }

        return $result;
    }

    public function home()
    {
        $model = $this->model();
        $alias = $model->alias();
        $categoryService = App::category();
        $bannerModel = App::load('banner', 'model');
        $root = $categoryService->root('product');
        $option = [
            'where' => 'status > 0',
            'select' => 'id, parent_id, seo_id'
        ];
        $product_category = $categoryService->tree('product', $option);

        $product_category = Hash::nest($product_category, [
            'idPath' => '{n}.id',
            'parentPath' => '{n}.parent_id',
            'root' => $root
        ]);
        $product_category = $product_category[0]['children'];

        $category = [];
        foreach ($product_category as $level1) {
            $level1_id = $level1['id'];
            $category[$level1_id][$level1_id] = $level1_id;
            foreach ($level1['children'] as $level2) {
                $level2_id = $level2['id'];
                $category[$level1_id][$level2_id] = $level2_id;
                foreach ($level2['children'] as $level3) {
                    $level3_id = $level3['id'];
                    $category[$level1_id][$level3_id] = $level3_id;
                }
            }
        }
        $product_category = Hash::combine($product_category, '{n}.id', '{n}');

        $default = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id",
            'where' => "{$alias}.status > 0",
            'order' => "{$alias}.id desc",
            'limit' => 8
        ];

        $result = [];
        foreach ($category as $id => $id_list) {
            if (empty($id_list)) {
                continue;
            }
            $query = $default;
            $query['where'] .= ' AND category_id IN (' . implode(',', $id_list) . ')';
            $product_list = $model->find($query);
            $banner = $bannerModel->find([
                'select' => 'id, category_id, title, content as url, file_id',
                'where' => 'status > 0',
                'order' => 'category_id, idx desc'
            ]);

            foreach ($product_list as $k => $product) {
                if (empty($product['product']['id'])) {
                    unset($product_list[$k]);
                }
            }
            
            if ($product_list) {
                $product_list = Hash::combine($product_list, '{n}.product.id', '{n}.product');
                $model->checkPromotion($product_list);
                $this->associate($product_list);

                $tmp = $product_category[$id];
                unset($tmp['children']);
                $result[$id] = [
                    'category' => $tmp,
                    'product' => $product_list,
                    'banner' => $banner
                ];
            }
        }

        return $result;
    }

    public function promote($limit = 20)
    {
        $alias = $this->model()->alias();

        $query = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id",
            'where' => 'CURDATE() BETWEEN extension.string_4 AND extension.string_5',
            'order' => 'extension.string_4 desc',
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
        $result = $this->model()->find($query);

        $result = Hash::combine($result, '{n}.product.id', '{n}.product');
        $this->model()->checkPromotion($result);
        $this->associate($result);

        usort($result, function ($a, $b) {
            return ($a['discount'] > $b['discount']) ? -1 : 1;
        });

        return $result;
    }

    public function bestSelling($limit = 8)
    {
        $query = [
            'select' => 'id, title, price, file_id, seo_id',
            'where' => 'status = 2',
            'order' => 'id desc',
            'limit' => $limit,
        ];

        $alias = $this->model()->alias();

        $result = $this->model()->find($query);
        $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");

        $this->model()->checkPromotion($result);
        $this->associate($result);

        return $result;
    }

    public function lastest($limit = 8)
    {
        $query = [
            'select' => 'id, title, price, file_id, seo_id',
            'where' => 'status > 0',
            'order' => 'id desc',
            'limit' => $limit
        ];

        $alias = $this->model()->alias();

        $result = $this->model()->find($query);
        $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");

        $this->model()->checkPromotion($result);
        $this->associate($result);

        return $result;
    }
}
