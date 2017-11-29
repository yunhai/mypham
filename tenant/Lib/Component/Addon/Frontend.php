<?php

use Mp\App;
use Mp\Lib\Session;
use Mp\Lib\Utility\Hash;
use Mp\Lib\Utility\Text;

class FrontendAddonComponent
{
    public function init()
    {
        $service = App::load('menu', 'service', [App::load('menu', 'model')]);
        $main_menu = $service->retrieve('frondend-main-menu');
        $offcanvas_menu = $service->retrieve('frondend-offcanvas-menu');

        $product_category = $this->getProductCategory();
        $product_category = Hash::combine($product_category, '{n}.id', '{n}');

        $cart = Session::read('cart');
        $addon = compact('main_menu', 'offcanvas_menu', 'product_category', 'cart');

        if ($cart) {
            $seo = Hash::combine($product_category, '{n}.id', '{n}.seo_id');
            $file = Hash::combine($cart['detail'], '{n}.id', '{n}.file_id');

            App::refer(compact('seo', 'file'));

            $addon['cart_total'] = $cart['total']['item'];
        }

        $addon['banner'] = $this->banner();

        $breadcrumb = $this->breadcrumb();
        $manufacturer = $this->manufacturer();

        $manufacturer = array_chunk($manufacturer, 6);

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

        foreach ($product_category['children'] as $level1 => &$item1) {
            foreach ($item1['children'] as $level2 => &$item2) {
                if (!$item2['children']) {
                    unset($item1['children'][$level2]);
                }
            }
            if (!$item1['children']) {
                unset($product_category['children'][$level1]);
            }
        }

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

        $breadcrumb = App::mp('view')->get('breadcrumb') ?: [];
        $request = App::mp('request');
        $module = $map[$request->query['module']] ?? [];
        if ($module) {
            array_unshift($breadcrumb, $module);
        }

        return $breadcrumb;
    }
}
