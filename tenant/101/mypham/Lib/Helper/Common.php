<?php

use Mp\App;
use Mp\Lib\Helper\Common;
use \Mp\Lib\Utility\Hash;

class CommonHelper extends Common
{

    public function lastest($model = null, $option = [], $association = [])
    {
        $alias = $model->alias();

        $result = $model->find($option);
        $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");
        $result = $model->associate($result, $association);

        if ($result) {
            $main = $list = $sub = [];
            $i = 0;
            foreach ($result as $id => $item) {
                $i++;
                if ($i == 1) {
                    $main = $item;
                    continue;
                }
                if ($i < 5) {
                    $list[$id] = $item;
                    continue;
                }

                $sub[$id] = $item;
            }

            return compact('main', 'list', 'sub');
        }

        return [];
    }
}