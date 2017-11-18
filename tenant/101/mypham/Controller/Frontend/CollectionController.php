<?php

use Mp\App;

App::uses('makeup', 'controller');

class CollectionController extends MakeupController
{
    public function __construct()
    {
        parent::__construct('collection', 'post', 'collection', 'collection');
    }
}
