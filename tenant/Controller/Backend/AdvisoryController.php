<?php

use Mp\App;

App::uses('Post', 'controller');

class AdvisoryController extends PostController
{
    public function __construct($model = 'advisory', $table = 'post', $alias = 'advisory', $template = 'advisory')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function index()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.created, {$alias}.modified, {$alias}.category_id",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);
        $data['category'] = $this->model()->category();

        $option = [
            'filter' => [
                'alias' => $alias,
                'category' => App::category()->flat($alias, false, 'title', '&nbsp;&nbsp;&nbsp;')
            ]
        ];

        $this->render('index', compact('data', 'option'));
    }

    public function edit($id = 0)
    {
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
            $data = $request->data[$alias];

            $replied = $target[$alias]['replied'];
            $error = [];
            if ($data['reply']) {
                $request->data[$alias]['replied'] = 1;
            }
            $flag = $this->save($request->data, $error);
            if ($flag) {
                if ($data['reply']) {
                    $info = [
                        'to' => $data['email']
                    ];
                    $this->email('m4003', $data, $info);
                    $request->data[$alias]['reply'] = 0;
                }
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $this->set('error', $error);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }
            $target = $this->formatPostData($request->data, $alias);
        }

        $this->attach($target, $alias);

        $option = [
            'category' => $this->getCategory($alias, true, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;')
        ];

        return $this->render('input', compact('target', 'option'));
    }

    protected function email($template = '', $data = [], $mail = [], $priority = 30)
    {
        $common = App::load('common');
        $common->sendEmail($template, $data, $mail);
    }
}
