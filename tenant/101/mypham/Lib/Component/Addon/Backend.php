<?php

use Mp\App;
use Mp\Lib\Utility\Hash;

class BackendAddonComponent
{
    public function init()
    {
        $menuService = App::load('menu', 'service', [App::load('menu', 'model')]);
        $menu = $menuService->retrieve('backend-left-sidebar');

        return compact('menu');
    }
}
