<?php

use Mp\App;
use Mp\Lib\Utility\Hash;

class BackendAddonComponent {

    public function init() {
        $menuService = App::load('menu', 'service', [App::load('menu', 'model')]);

        $tmp = $menuService->retrieve('backend-left-sidebar');

        $menu = [];
        foreach($tmp as $item) {
            $id = $item['menu']['id'];
            $menu[$id] = $item['menu'];
            if ($item['children']) {
                foreach($item['children'] as $child) {
                    $childId = $child['menu']['id'];
                    $menu[$id]['children'][$childId] = $child['menu'];
                }
            }
        }

        $tmp = $menuService->retrieve('backend-right-sidebar');

        $submenu = [];
        foreach($tmp as $item) {
            foreach($item['children'] as $child) {
                $childId = $child['menu']['id'];
                $submenu[$item['menu']['url']][$childId] = $child['menu'];
            }
        }

        return compact('menu', 'submenu');
    }
}