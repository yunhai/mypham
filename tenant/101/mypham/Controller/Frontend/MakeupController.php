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
        $request = App::mp('request');
        $alias = $this->model()->alias();

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.content, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id",
            'where' => 'status = 1',
            'order' => "{$alias}.id desc",
            'page' => $page,
            'limit' => 6,
            'paginator' => [
                'navigator' => false
            ]
        ];

        $pager = [];
        $data = $this->paginate($option, true, $pager);

        $data['list'] = Hash::combine($data['list'], "{n}.{$alias}.id", "{n}.{$alias}");

        $this->associate($data['list']);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $option = [
            'page' => $pager,
            'current_url' => App::load('url')->current()
        ];

        $lastest = array_splice($data['list'], 0, 5);
        $focus = array_shift($lastest);

        $this->sidebar();
        $this->render('index', compact('lastest', 'focus', 'option', 'data'));
    }

    private function loadAjax($data = [])
    {
        $item_list = $data['list'] ?? [];
        $data = [
            'total' => $data['page']['total'] ?? 0,
            'current' => $data['page']['current'] ?? 0,
            'html' => $this->render('item_list', compact('item_list')),
        ];

        return $this->renderJson($data);
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
        $sidebar = $this->sidebar();

        if ($sidebar['category']) {
            $breadcrumb = [
                $sidebar['category'][$target['category_id']],
                $target
            ];
        }

        $breadcrumb[] = $target;

        $this->set('breadcrumb', $breadcrumb);
        $this->render('detail', compact('target', 'others'));
    }

    public function sidebar()
    {
        $alias = $this->model()->alias();
        $service = App::category();
        $root = $service->root($alias);

        $category = $service->tree($alias, ['select' => 'seo_id', 'where' => 'status > 0']);
        array_shift($category);
        $category = Hash::combine($category, '{n}.id', '{n}');

        $title = [
            'makeup' => 'Cẩm nang làm đẹp',
            'collection' => 'Bộ sưu tập',
            'advisory' => 'Tư vấn'
        ];

        $sidebar = [
            'category' => $category,
            'alias_name' => $title[$alias]
        ];

        $this->variable(compact('sidebar'));

        return $sidebar;
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
            'select' => "{$alias}.id, {$alias}.title, {$alias}.content, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id",
            'where' => 'status = 1 AND category_id = ' . $category,
            'order' => "{$alias}.id desc",
            'page' => $page,
            'limit' => 6,
            'paginator' => [
                'navigator' => false
            ]
        ];

        $pager = [];
        $data = $this->paginate($option, true, $pager);
        $data['list'] = Hash::combine($data['list'], "{n}.{$alias}.id", "{n}.{$alias}");

        $this->associate($data['list']);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $option = [
            'page' => $pager,
            'current_url' => App::load('url')->current(),
            'category_id' => $category
        ];

        $lastest = array_splice($data['list'], 0, 5);
        $focus = array_shift($lastest);

        $breadcrumb = [
            'category' => [
                'title' => $categories[$category],
            ],
        ];
        $this->set('breadcrumb', $breadcrumb);
        $sidebar = $this->sidebar();
        $this->render('index', compact('lastest', 'focus', 'option', 'data'));
    }
}
