<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Backend\Post;

class StoreController extends Post {

    public function __construct($model = 'store', $table = 'post', $alias = 'store', $template = '') {
        parent::__construct($model, $table, $alias, $template);

        $this->model()->category(App::category()->flat('store'));

        $virtualField = [
            'string_1' => 'address',
            'string_2' => 'phone',
            'string_3' => 'product_category',
            'string_4' => 'product_amout',
            'string_5' => 'logo',
            'text_1' => [
                'email',
                'location',
                'summary'
            ]
        ];

        $this->model()->loadExtension(new \Mp\Model\Extension());
        $this->model()->virtualField($virtualField);
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'search':
                $this->search();
                break;
            default :
                parent::navigator();
                break;
        }
    }

    public function edit($id = 0) {
        $request = App::mp('request');

        $id = intval($id);

        $alias = $this->model()->alias();
        $fields = "{$alias}.id, {$alias}.title, {$alias}.category_id, {$alias}.idx, {$alias}.content, {$alias}.status, {$alias}.seo_id, {$alias}.file_id";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        $seo = App::mp('seo')->target($target[$alias]['seo_id']);
        $target = array_merge($target, $seo);

        if (!empty($request->data[$alias])) {
            $error = [];

            $flag = $this->save($request->data, $error);
            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', $error);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }

            $target = $this->formatPostData($request->data, $alias);
        }

        $this->attach($target, $alias);

        return $this->render('input', compact('target'));
    }

    public function search() {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        $term = $request->param['q'];
        if (mb_strlen($term) < 3) {
            $this->renderJson([]);
        }

        $page = 1;

        $virtualField = [
            'string_1' => 'address',
            'string_2' => 'phone',
            'string_5' => 'logo',
            'text_1' => [
                'email',
                'summary'
            ]
        ];
        $this->model()->virtualField($virtualField);

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.category_id",
            'order' => "{$alias}.id desc",
            'where' => "{$alias}.title like '%{$term}%'",
            'page' => $page
        ];

        $data = $this->model()->find($option);
        $data = Hash::combine($data, "{n}.{$alias}.id", "{n}.{$alias}");
        $this->renderJson($data);
    }

    public function attach(&$target = [], $alias = '', $fields = ['logo' => 'logo']) {
        parent::attach($target, $alias, $fields);
    }
}
