<?php

use Mp\App;
use Mp\Lib\Session;
use Mp\Lib\Utility\Hash;
use Mp\Lib\Utility\Text;

class FrontendAddonComponent
{
    public function init()
    {
        $main_menu = App::load('menu', 'service', [App::load('menu', 'model')])->retrieve('frondend-main-menu');
        $main_menu = Hash::combine($main_menu, '{n}.menu.id', '{n}.menu');

        $product_category = $this->getProductCategory();
        $product_category = Hash::combine($product_category, '{n}.id', '{n}');
        
        $cart = Session::read('cart');
        $order = Session::read('order');
        $addon = compact('main_menu', 'product_category', 'cart', 'order');

        if ($cart) {
            $seo = Hash::combine($product_category, '{n}.id', '{n}.seo_id');
            $file = Hash::combine($cart['detail'], '{n}.id', '{n}.file_id');

            App::refer(compact('seo', 'file'));
            
            $addon['cart_total'] = $cart['total']['item'];
        }

        $banner = $this->banner();
        $breadcrumb = $this->breadcrumb();
        $manufacturer = $this->manufacturer();
        
        return compact('addon', 'breadcrumb', 'banner', 'manufacturer');
    }

    private function manufacturer()
    {
        $service = App::load('manufacturer', 'service');
        $tmp = $service->all();
        foreach ($tmp as $id => $item) {
            $tmp[$id]['slug'] = Text::slug($item['title']) . '-' . $id;
        }
        App::associate($tmp);

        return $tmp;
    }

    private function getProductCategory()
    {
        $service = App::category();

        $root = $service->root('product');
        $product_category = $service->tree('product', ['select' => 'seo_id', 'where' => 'status > 0']);

        $product_category = Hash::nest($product_category, [
            'idPath' => '{n}.id',
            'parentPath' => '{n}.parent_id',
            'root' => $root
        ]);

        $product_category = current($product_category);

        return $product_category['children'];
    }

    private function banner()
    {
        $model = App::load('banner', 'model');
        $category = App::category()->flat($model->alias(), true, 'slug', '', ['select' => 'id, slug']);

        $model->category($category);

        $option = [
            'select' => 'id, category_id, title, content as url, file_id',
            'where' => 'status > 0',
            'order' => 'category_id, idx desc'
        ];
        $tmp = $model->find($option);
        App::associate(Hash::combine($tmp, '{n}.banner.id', '{n}.banner'));

        $tmp = Hash::combine($tmp, '{n}.banner.id', '{n}.banner', '{n}.banner.category_id');

        $result = [];
        foreach ($tmp as $categoryId => $list) {
            $name = $category[$categoryId];
            if ($name == 'san-pham') {
                $list = Hash::combine($list, '{n}.id', '{n}', '{n}.sub_category_id');
            }
            $result[$name] = $list;
        }
        // print_r('<pre>');
        // print_r($result);
        // print_r('</pre>');
        // exit();

        return $result;
    }

    private function breadcrumb()
    {
        $map = [
            'contact' => [
                'title' => 'Liên hệ',
                'url' => 'contact'
            ],
            'advisory' => [
                'title' => 'Tư vấn',
                'url' => 'advisory'
            ],
            'collection' => [
                'title' => 'Bộ sưu tập',
                'url' => 'collection'
            ],
            'customer' => [
                'title' => 'Chăm sóc khách hàng',
                'url' => 'customer'
            ],
            'makeup' => [
                'title' => 'Cẩm nang làm đẹp',
                'url' => 'makeup'
            ],
            'store' => [
                'title' => 'Hệ thống chi nhánh',
                'url' => 'store'
            ],
            'cart' => [
                'title' => 'Giỏ hàng',
                'url' => 'cart'
            ],
            'order' => [
                'title' => 'Đơn hàng',
                'url' => 'order'
            ],
            'product' => [
                'title' => 'Sản phẩm',
                'url' => '#',
            ],
            'user' => [
                'title' => 'Tài khoản',
                'url' => 'user'
            ]
        ];

        $breadcrumb = App::mp('view')->get('breadcrumb');
        if (empty($breadcrumb)) {
            $request = App::mp('request');
            $module = isset($map[$request->query['module']]) ? $map[$request->query['module']] : [];
            if ($module) {
                $breadcrumb = [
                    $module
                ];
            }
        } else {
            $seo = [];
            foreach ($breadcrumb as $key => $value) {
                if (isset($value['seo_id'])) {
                    $seo[] = $value['seo_id'];
                }
            }
            App::refer(['seo' => $seo]);
        }

        return $breadcrumb;
    }
}
