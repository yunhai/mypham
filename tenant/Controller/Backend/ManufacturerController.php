<?php

use Mp\App;
use Mp\Lib\Utility\Hash;

App::uses('Post', 'controller');

class ManufacturerController extends PostController
{
    public function __construct($model = 'manufacturer', $table = 'post', $alias = 'manufacturer', $template = 'manufacturer')
    {
        parent::__construct($model, $table, $alias, $template);
        $this->model()->category(App::category()->flat('manufacturer'));

        $virtualField = [
            'string_1' => 'origin',
        ];

        $this->model()->loadExtension(new \Mp\Model\Extension());
        $this->model()->virtualField($virtualField);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'search':
                $this->search();
                break;
            default:
                parent::navigator();
                break;
        }
    }

    public function search()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        $term = $request->param['q'];
        if (mb_strlen($term) < 3) {
            $this->renderJson([]);
        }

        $page = 1;

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.category_id",
            'order' => "{$alias}.id desc",
            'where' => "{$alias}.title like '%{$term}%'",
            'page' => $page
        ];

        $data = $this->model()->find($option);
        $data = Hash::combine($data, "{n}.{$alias}.id", "{n}.{$alias}");
        foreach ($data as $key => $item) {
            $data[$key]['title_origin'] = $item['title'];
            if ($item['origin']) {
                $data[$key]['title_origin'] = "{$item['title']} [{$item['origin']}]";
            }
        }

        $this->renderJson($data);
    }
}
