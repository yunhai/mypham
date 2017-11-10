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

        $top_banner = $this->banner();
        $home_product = $service->home();
        $hot_product = $service->hot(8); // san pham hot
        $promote_product = $service->promote(7); // san pham khuyen mai
        
        $best_selling_product = $service->bestSelling(10);

        $this->render('index', compact('top_banner', 'home_product', 'promote_product', 'hot_product', 'best_selling_product'));
    }

    private function banner()
    {
        $model = App::load('banner', 'model');
        $option = [
            'select' => 'id, slug',
            'where' => 'slug = "home-slideshow"'
        ];
        $category = App::category()->flat($model->alias(), true, 'slug', '', $option);

        $model->category($category);

        $option = [
            'select' => 'id, category_id, title, content as url, file_id',
            'where' => 'status > 0',
            'order' => 'category_id, idx desc'
        ];
        $banner = $model->find($option);

        $banner = Hash::combine($banner, '{n}.banner.id', '{n}.banner');

        App::associate($banner);

        return $banner;
    }
}
