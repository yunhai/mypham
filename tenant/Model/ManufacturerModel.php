<?php

use Mp\Model\Post;

class ManufacturerModel extends Post
{
    use \Mp\Lib\Traits\Extension;

    public function __construct($table = 'post', $alias = 'manufacturer')
    {
        parent::__construct($table, $alias);
    }
}
