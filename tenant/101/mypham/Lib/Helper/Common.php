<?php

use Mp\App;
use Mp\Lib\Helper\Common;
use Mp\Lib\Utility\Hash;

class CommonHelper extends Common
{
    // public function lastest($model = null, $option = [], $association = ['seo' => 'id', 'file' => 'id'])
    // {
    //     $alias = $model->alias();
    //
    //     $result = $model->find($option);
    //
    //     $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");
    //     App::associate($result);
    //
    //     return $result;
    // }
}
