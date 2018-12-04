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

    private function banner()
    {
        $service = App::load('banner', 'service');
        $slider = $service->getByCategorySlug('home-slideshow');
        $header = $service->getByCategorySlug('home-header');

        return compact('slider', 'header');
    }
}
