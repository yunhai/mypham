<?php

use Mp\App;
use Mp\Core\Controller\Frontend;
use Mp\Lib\Utility\Hash;

class HomeController extends Frontend
{
    public function __construct($model = '', $table = '', $alias = '', $template = 'home')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');
        $helper = App::mp('config');

        switch ($request->query['action']) {
            default:
                $this->index();
                break;
        }
    }

    public function index()
    {
        $service = App::load('product', 'service');

        $banners = $this->banner();
        $slider_banner = $banners['slider'] ?? [];
        $header_banner = $banners['header'] ?? [];

        $home_product = $service->home();

        // $promote_product = $service->promote(7); // san pham khuyen mai
        $best_selling_product = $service->bestSelling(10); // san pham ban chay

        $this->render('index', compact('slider_banner', 'header_banner', 'home_product', 'promote_product', 'best_selling_product'));
    }

    public function product()
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

        $option = [
            'where' => 'status > 0',
            'select' => 'id, parent_id, slug'
        ];
        $bannerCategoryId = 0;
        $bannerCategory = $categoryService->tree('banner', $option);
        foreach($bannerCategory as $id => $item) {
            if ($item['slug'] === 'home-product') {
                $bannerCategoryId = $id;
                break;
            }
        }
        $bannerModel->category($bannerCategory);
        $home_banner = $bannerModel->find([
            'select' => 'banner.id, banner.category_id, banner.title, banner.content as url, banner.file_id, extension.string_1 as sub_category',
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'left',
                    'condition' => 'extension.target_model = "banner"'
                ],
            ],
            'where' => 'status > 0 AND category_id = ' . $bannerCategoryId,
            'order' => 'category_id, idx desc'
        ]);

        $home_banner = Hash::combine($home_banner, '{n}.banner.id', '{n}.banner');
        App::associate($home_banner);
        $home_banner = Hash::combine($home_banner, '{n}.id', '{n}', '{n}.sub_category_id');

        $bannerModel->category($bannerCategory);
        $result = [];
        foreach ($category as $id => $id_list) {
            if (empty($id_list)) {
                continue;
            }
            $category_id_string = implode(',', $id_list);

            $query = $default;
            $query['where'] .= ' AND category_id IN (' . $category_id_string . ')';
            $product_list = $model->find($query);

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

                $banner = [];
                foreach($home_banner as $category_id => $item) {
                    if (in_array($category_id, $id_list)) {
                        $banner = array_merge($banner, $item);
                    }
                }

                $result[$id] = [
                    'category' => $tmp,
                    'product' => $product_list,
                    'banner' => $banner
                ];
            }
        }

        return $result;
    }

    private function banner()
    {
        $service = App::load('banner', 'service');
        $slider = $service->getByCategorySlug('home-slideshow');
        $header = $service->getByCategorySlug('home-header');

        return compact('slider', 'header');
    }
}
