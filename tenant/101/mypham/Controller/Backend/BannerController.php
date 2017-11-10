<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Backend\Post;

class BannerController extends Post
{
    public function __construct($model = 'banner', $table = 'post', $alias = 'banner', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function add()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $target = $this->model()->init();
        $target = array_merge($target, App::mp('seo')->target());

        if (!empty($request->data[$alias])) {
            $error = [];
            if (empty($request->data[$alias]['category_id'])) {
                $category_id = $this->model()->category();
                reset($category_id);
                $category_id = key($category_id);

                $request->data[$alias]['category_id'] = $category_id;
            }
            $flag = $this->save($request->data, $error);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');

                return $this->redirect(App::load('url')->module());
            }

            $this->set('error', $error);
            $this->flash('edit', __('m0002', 'Please review your data.'), 'error');

            $target = $this->formatPostData($request->data, $alias);
        }

        $this->attach($target, $alias);

        $option = [
            'category' => $this->getCategory($alias),
            'sub_category' => $this->getCategory('product'),
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function edit($id = 0)
    {
        $request = App::mp('request');

        $id = (int) $id;

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

        $option = [
            'category' => $this->getCategory($alias, true, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;'),
            'sub_category' => $this->getCategory('product'),
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function index()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];
        $option = [
            'select' => "{$alias}.id,  {$alias}.title, {$alias}.category_id, {$alias}.idx, {$alias}.status, {$alias}.content as url",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);
        $data['category'] = $this->model()->category();
        $data['sub_category'] = $this->getCategory('product', true, 'title', '');
        $option = [
            'filter' => [
                'alias' => $alias,
                'category' => App::category()->flat($alias, false, 'title', '&nbsp;&nbsp;&nbsp;')
            ]
        ];

        $this->render('index', compact('data', 'option'));
    }

    protected function makeFilter($criteria = [], $token = '', $filter = '')
    {
        $request = App::mp('request');
        $category = '';
        $alias = $this->model()->alias();
        $categories = $this->model()->category();

        if (isset($criteria['category'])) {
            $category = implode(',', array_keys(App::category()->extract($criteria['category'])));
        }
        if (empty($category)) {
            $category = implode(',', array_keys($categories));
        }

        $where = $alias . '.category_id IN (' . $category . ')';
        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $where .= ' AND ' . $alias . '. status IN (' . $criteria['status'] . ')';
        }

        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.category_id, {$alias}.content as url, {$alias}.idx",
            'where' => $where,
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];
        $data = $this->paginate($option, true);

        $data['category'] = $categories;

        $option = [
            'filter' => [
                'alias' => $alias,
                'category' => App::category()->flat($alias, false, 'title', '&nbsp;&nbsp;&nbsp;'),
                'token' => $token,
                'filter' => $filter
            ]
        ];

        $this->render('search', compact('data', 'option'));
    }

    public function saveSearch($data, $group = [])
    {
        return true;
    }
}
