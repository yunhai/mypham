<?php

use Mp\Controller\Backend\Category;

use Mp\App;
use Mp\Lib\Utility\Text;
use Mp\Core\Controller\Backend;

class LocationController extends Category
{
    public function __construct($model = 'location', $table = 'category', $alias = 'location', $template = 'location')
    {
        parent::__construct($model, $table, $alias, $template);

        $virtualField = [
            'string_1' => 'delivery_price',
            'string_2' => 'delivery_day',
        ];

        $this->model()->loadExtension(new \Mp\Model\Extension());
        $this->model()->virtualField($virtualField);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'add':
                    $this->add('location');
                break;
            case 'edit':
                     $this->edit('location', $request->query[2]);
                break;
            case 'update':
                    $this->update($request->query[2]);
                break;
            case 'delete':
                    $this->delete();
                break;
            case 'group':
                    $this->group();
                break;
            default:
                    $this->index();
                break;
        }
    }

    public function index($group = 'location')
    {
        return parent::index($group);
    }

    public function add($group = 'location')
    {
        return parent::add($group);
    }

    public function edit($group = 'location', $id = 0)
    {
        return parent::edit($group, $id);
    }

    public function delete()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        if (!empty($request->data[$alias])) {
            $condition = 'id IN (' . implode(',', $request->data[$alias]) . ')';
            $this->model()->delete($condition, $alias, ['seo_id']);
        }

        return $this->back();
    }
}