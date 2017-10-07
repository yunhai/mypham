<?php

use Mp\Model\Post;

class BannerModel extends Post
{
    use \Mp\Lib\Traits\Extension;

    public function __construct($table = 'post', $alias = 'banner')
    {
        parent::__construct($table, $alias);
        $virtualField = [
            'string_1' => 'sub_category_id',
        ];

        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField($virtualField);
    }
}
