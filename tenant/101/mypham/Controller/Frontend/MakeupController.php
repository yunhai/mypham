<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Controller\Frontend;

class MakeupController extends Frontend
{
    public function __construct($model = 'makeup', $table = 'post', $alias = 'makeup', $template = 'makeup')
    {
        parent::__construct($model, $table, $alias, $template);
        $this->model()->category(App::category()->flat($alias, false, 'title', '', ['where' => 'status > 0']));
    }

    public function index()
    {
        $seoId = $fileId = [];
        $alias = $this->model()->alias();

        $query = [
            'select' => 'id, title, content, file_id, seo_id',
            'where' => 'status = 1',
            'order' => 'id desc',
            'limit' => 9
        ];
        $posts = App::load('common')->lastest($this->model(), $query);

        if (isset($posts['main'])) {
            $seoId = [$posts['main']['seo_id']];
            $fileId = [$posts['main']['file_id']];
        }

        if (isset($posts['list'])) {
            $seoId = array_merge($seoId, Hash::combine($posts['list'], '{n}.seo_id', '{n}.seo_id'));
            $fileId = array_merge($fileId, Hash::combine($posts['list'], '{n}.file_id', '{n}.file_id'));
        }

        $category = $this->model()->category();

        $option = [
            'category' => $category,
            'postByCategory' => []
        ];

        foreach ($category as $id => $element) {
            $query['where'] = 'category_id = ' . $id . ' AND status > 0';
            $tmp = $this->model()->find($query);
            $tmp = Hash::combine($tmp, "{n}.{$alias}.id", "{n}.{$alias}");

            if ($tmp) {
                $seoId = array_merge($seoId, Hash::combine($tmp, '{n}.seo_id', '{n}.seo_id'));
                $fileId = array_merge($fileId, Hash::combine($tmp, '{n}.file_id', '{n}.file_id'));
            }
            $option['postByCategory'][$id] = $tmp;
        }
        $option['postByCategory'] = array_filter($option['postByCategory']);

        $seoId = array_merge($seoId, App::category()->seoId(array_keys($category)));
        $this->refer(['seo' => $seoId, 'file' => $fileId]);

        $this->render('index', compact('posts', 'option'));
    }

    public function detail($id = 0)
    {
        $alias = $this->model()->alias();

        $select = "{$alias}.id, {$alias}.title, {$alias}.content, {$alias}.modified, {$alias}.category_id";
        $where = "{$alias}.id = {$id} AND {$alias}.status = 1";

        $target = $this->model()->find(compact('select', 'where'), 'first');
        if (empty($target)) {
            abort('NotFoundException');
        }

        $target = $target[$alias];
        $others = $this->other($target);
        $this->sidebar($id);

        $this->render('detail', compact('target', 'others'));
    }

    public function sidebar($current = 0)
    {
        $model = $this->model();
        $alias = $model->alias();
        $ids = array_keys($model->category());
        $service = App::category();

        $query = [
            'select' => 'id, title, seo_id',
            'where' => 'id IN (' . implode(',', $ids) . ') AND status = 1',
            'order' => 'idx',
        ];

        $category = $service->model()->find($query);
        $category = Hash::combine($category, '{n}.category.id', '{n}.category');

        $this->refer(['seo' => Hash::combine($category, '{n}.seo_id', '{n}.seo_id')]);
        $topCategory = array_shift($category);

        $select = 'id, title, file_id, seo_id';
        $where = "id <> {$current} AND status = 1";
        $order = 'id desc';
        $limit = 5;
        $random = $model->find(compact('select', 'where', 'order', 'limit'));
        $random = Hash::combine($random, "{n}.{$alias}.id", "{n}.{$alias}");

        $this->associate($random);
        $this->variable(compact('category', 'topCategory', 'random'));
    }

    public function category($category = 0)
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();

        $categories = $this->model()->category();
        if (!in_array($category, array_keys($categories))) {
            abort('NotFoundException');
        }

        $page = empty($request->name['page']) ? 1 : $request->name['page'];
        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.content, {$alias}.file_id, {$alias}.seo_id",
            'where' => 'status  = 1 AND category_id = ' . $category,
            'order' => "{$alias}.id desc",
            'page' => $page,
            'limit' => 10,
            'paginator' => [
                'navigator' => false
            ]
        ];

        $data = $this->paginate($option, true);
        $data['list'] = Hash::combine($data['list'], "{n}.{$alias}.id", "{n}.{$alias}");

        $this->associate($data['list']);

        $data['category'] = $categories;
        $this->sidebar(0);
        $this->render('category', compact('data'));
    }
}
