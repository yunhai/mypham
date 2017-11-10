<?php

use Mp\Service\Category;

class CategoryService extends Category {

    public function init($list = [], $appId = 0) {
        if (empty($list)) {
            return true;
        }

        $flag = true;
        $locales = App::mp('config')->get('locale');
        foreach($locales['available'] as $lo => $ignore) {
            foreach ($list as $v) {
                $data = [
                    'locale' => $lo+1,
                    'title' => $v,
                    'slug' => $v,
                    'lft' => 1,
                    'rght' => 2,
                    'app_id' => $appId
                ];

                $flag = $this->model()->save($data);
                if ($flag == false) {
                    return false;
                }

                $lastInsertId = $this->model()->lastInsertId();
                $temp['id'] = $lastInsertId;
                $temp['tree_id'] = $lastInsertId;

                $flag = $this->model()->save($temp);
                if ($flag == false) {
                    return false;
                }
            }
        }

        return $flag;
    }
}
