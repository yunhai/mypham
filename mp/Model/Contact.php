<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;
use Mp\Lib\Utility\Hash;

class Contact extends Model
{
    public function __construct($table = 'contact', $alias = 'contact')
    {
        parent::__construct($table, $alias);
    }

    public function beforeSave(&$data = [])
    {
        parent::beforeSave($data);
        $data['app_id'] = App::mp('login')->targetId();
    }

    public function baseCondition()
    {
        return parent::baseConditionWithAppId();
    }
}
