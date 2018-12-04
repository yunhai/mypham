<?php

use Mp\Service\Post;
use Mp\App;
use Mp\Lib\Utility\Hash;

class BannerService extends Post {

    public function __construct($model = 'banner', $table = 'post', $alias = 'banner')
    {
        $this->model(App::load($model, 'model', compact('table', 'alias')));
        $this->model()->category(App::category()->flat($alias, false, 'id', '', ['where' => 'status > 0']));
    }

    public function getByCategorySlug($slug_name) {
        $option = [
            'select' => 'id, slug',
            'where' => 'slug = "' . $slug_name . '"'
        ];
        $category = App::category()->flat($this->model->alias(), true, 'slug', '', $option);

        $option = [
            'select' => 'id, category_id, title, content as url, file_id',
            'where' => 'status > 0 AND category_id IN (' . implode(',', array_keys($category)) . ')',
            'order' => 'category_id, idx desc'
        ];
        $banner = $this->model->find($option);

        $banner = Hash::combine($banner, '{n}.banner.id', '{n}.banner');
        App::associate($banner);

        return $banner;
    }
}
