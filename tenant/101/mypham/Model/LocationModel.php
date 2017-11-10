<?php

use Mp\App;
use Mp\Model\Category;
use Mp\Lib\Utility\Hash;

class LocationModel extends Category
{
    use \Mp\Lib\Traits\Extension;

    public function __construct($table = 'category', $alias = 'location') {
        parent::__construct($table, $alias);
    }

    public function field()
    {
        return [
            'string_1' => 'delivery_price',
            'string_2' => 'delivery_day',
        ];
    }

    public function delete($condition = '', $branch = '', $association = [])
    {
        return parent::delete($condition, $branch, $association);
    }

    public function list()
    {
        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField($this->field());

        $alias = $this->alias;
        $service = App::load($this->alias, 'service', [$this]);

        $root = $service->root($this->alias);
        $default = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.slug, {$alias}.parent_id, {$alias}.status, {$alias}.idx",
        ];

        $result = $service->extract($root, false, 'title', '', $default);
        return Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}", "{n}.{$alias}.parent_id");
    }
}
