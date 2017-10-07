<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Lib\Utility\Text;
use Mp\Lib\Helper\Security;
use Mp\Core\Controller\Frontend;

class ProductController extends Frontend
{
    public function __construct($model = 'product', $table = 'product', $alias = 'product', $template = 'product')
    {
        parent::__construct($model, $table, $alias, $template);

        $category = App::category()->tree($alias, ['select' => 'seo_id', 'where' => 'status > 0']);
        $this->model()->category($category);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'faq':
                $this->faq($request->query[2]);
                break;
            case 'ask':
                $this->ask($request->query[2]);
                break;
            case 'rating':
                $this->rating($request->query[2]);
                break;
            case 'vote':
                $this->vote($request->query[2]);
                break;
            case 'hot':
                $this->hot();
                break;
            case 'best_sell':
                $this->bestSellFromShop();
                break;
            case 'manufacturer':
                $this->manufacturer($request->query[2]);
                break;
            case 'promote':
                $this->promote();
                break;
            case 'category':
                $this->category($request->query[2]);
                break;
            case 'detail':
                $this->detail($request->query[2]);
                break;
            case 'search':
                $this->search();
                break;
            default:
                parent::navigator();
                break;
        }
    }

    private function appApi()
    {
        $model = new \Mp\Model\Apps();

        return $model->api(App::load('login')->targetId());
    }

    public function search()
    {
        $request = App::mp('request');

        $keyword = $category = $token = '';
        if (!empty($request->data) && empty($request->data['ajax'])) {
            extract($request->data);

            $api = $this->appApi();
            $token = "category={$category}&keyword=$keyword";

            $security = new \Mp\Lib\Helper\Security();
            $token = $security->encrypt($token, $api, 2);
        } elseif (isset($request->get()['request'])) {
            $tmp = $request->get()['request'];
            $tmp = explode('/', $tmp);

            $token = '';
            foreach ($tmp as $value) {
                if (mb_strpos($value, 'token:') === 0) {
                    $token = str_replace('token:', '', $value);
                    break;
                }
            }
            if ($token) {
                $token = trim($token, '/');
                $api = $this->appApi();

                $security = new \Mp\Lib\Helper\Security();
                $q = $security->decrypt($token, $api, 2);

                $tmp = explode('&', $q);
                foreach ($tmp as $str) {
                    list($key, $value) = explode('=', $str);
                    $$key = $value;
                }
            }
        }

        $page = $data = $search = [];
        if (!empty($keyword)) {
            $alias = $this->model()->alias();

            $model = new \Mp\Model\Search();

            $index = 0;
            $keyword = Text::slug($keyword, '');
            $keywordArray = explode(' ', $keyword);

            $match = '';
            $length = count($keywordArray);
            foreach ($keywordArray as $value) {
                $index++;
                if ($index == $length) {
                    $match .= ' < '.$value;
                } else {
                    $match .= ' <(>'.$value;
                }
            }
            $match = trim($match, ' <(>');

            for ($i = 1; $i < $index - 1; $i++) {
                $match .= ')';
            }

            $match = "MATCH(keyword) AGAINST ('>{$match}' IN BOOLEAN MODE)";
            $match = "keyword LIKE '%{$keyword}%'";
            $match = 1;
            $option = [
                'select' => 'id, target_id',
                'where' => $match . ' AND target_model = "' . $alias . '"',
                'limit' => '1000'
            ];
            $data = [];
            $tmp = $model->find($option, 'all', 'target_id');
            if ($tmp) {
                if (empty($category)) {
                    $category = implode(',', array_keys($this->model()->category()));
                }

                $id = implode(',', array_keys($tmp));
                $option = [
                    'where' => "{$alias}.status > 0 AND {$alias}.id IN (" . $id . ") AND {$alias}.category_id IN ({$category})",
                    'limit' => 5
                ];
                $data = $this->filter($option);
            }
            $search = [
                'category' => $category,
                'keyword' => $keyword
            ];

            $page = $data['page'];
        }

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $default = [
            'page_name' => 'Tìm kiếm',
            'token' => $token,
            'search' => $search
        ];

        $option = $this->sideBar();
        $option = array_merge($option, $default);

        $current_url = App::load('url')->current();
        $this->render('search', compact('data', 'option', 'current_url', 'page'));
    }

    public function extend()
    {
        $virtualField = [
            'string_1' => 'promote',
            'string_2' => 'store_id',
            'string_3' => 'manufacturer',
            'string_4' => 'promote_start',
            'string_5' => 'promote_end',
            'text_1' => [
                'point',
                'option',
                'code',
                'gallery',
                'files'
            ]
        ];

        $this->model()->extend($virtualField);
    }

    public function detail($id = 0)
    {
        $this->extend();
        $alias = $this->model()->alias();

        $where = "{$alias}.id = {$id} AND {$alias}.status > 0";

        $target = $this->model()->find(compact('where'), 'first');
        if (empty($target)) {
            abort('NotFoundException');
        }

        $target = $target[$alias];
        $this->checkPromotion($target);

        $service = App::load('file', 'service');

        if (!empty($target['gallery'])) {
            $target['gallery'] = $service->target($target['gallery'], ['order' => 'idx']);
        }

        if (!empty($target['files'])) {
            $target['files'] = $service->target($target['files'], ['order' => 'idx']);
        }

        $option = [];
        $option['others'] = $this->other($target);

        $service = App::load('product', 'service');
        $service->model()->removeExtension();
        $option['promotion'] = $service->promote(12);
        $this->associate($option['promotion']);

        $service = App::category();
        $category = $service->tree('product', ['select' => 'id, title, seo_id', 'where' => 'status > 0']);
        $option['category'] = $category;
        array_shift($option['category']);
        $option['category'] = Hash::combine($category, '{n}.id', '{n}.title');

        $category = $category[$target['category_id']];

        $breadcrumb = [
            'category' => [
                'id' => $category['id'],
                'title' => $category['title'],
                'alias' => 'category',
                'seo_id' => $category['seo_id']
            ],
            'target' => [
                'id' => $target['id'],
                'title' => $target['title'],
                'alias' => $alias,
                'seo_id' => $target['seo_id']
            ]
        ];
        $this->set('breadcrumb', $breadcrumb);

        $manufacturer = $target['manufacturer'];
        $model = App::load('manufacturer', 'model');
        $model->category(App::category()->flat('manufacturer'));

        $select = 'manufacturer.id, manufacturer.title, manufacturer.seo_id';
        $where = "manufacturer.id = {$manufacturer} AND manufacturer.status > 0";

        $manufacturer = $model->find(compact('select', 'where'), 'first');
        $manufacturer = current($manufacturer);

        $target['manufacturer_target'] = $manufacturer;
        $this->associate($manufacturer);

        $this->render('detail', compact('target', 'option', 'manufacturer'));
    }

    private function checkPromotion(&$target = [])
    {
        $today = date('Y-m-d');

        $target['discount'] = 0;
        if ($target['promote_start'] <= $today && $today <= $target['promote_end']) {
            if ($target['price']) {
                $discount = ((int) (($target['promote'] / $target['price']) * 100));
                if ($discount) {
                    $target['discount'] = 100 - $discount;
                }
            }

            if (!empty($target['option_promotion'])) {
                foreach ($target['option_promotion'] as $index => $promotion) {
                    $price = $target['option_price'][$index];
                    $discount = ((int) (($promotion / $price) * 100));
                    if ($discount) {
                        $target['option_discount'][$index] = 100 - $discount;
                    }
                }
            }
        }
    }

    protected function other($target, $option = [])
    {
        $alias = $this->model()->alias();

        $id = $target['id'];
        $category = $target['category_id'];

        $service = App::category();
        $tmp = $service->extract($category);

        if (empty($tmp) === false) {
            $category = implode(',', array_keys($tmp));
        }

        $select = "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.file_id, {$alias}.seo_id";
        $where = "{$alias}.id <> {$id} AND {$alias}.status > 0 AND {$alias}.category_id IN ({$category})";
        $order = "{$alias}.id desc";
        $limit = 12;

        $others = $this->model()->find(compact('select', 'where', 'limit', 'order'));

        $others = Hash::combine($others, '{n}.' . $alias . '.id', '{n}.' . $alias);
        $this->associate($others);

        foreach ($others as $key => &$item) {
            $this->model()->promotion($item);
        }

        return $others;
    }

    public function promote()
    { //khuyen mai hot
        $model = $this->model();
        $alias = $model->alias();

        $option = [
            'where' => "{$alias}.status = 2 AND CURDATE() BETWEEN extension.string_4 AND extension.string_5"
        ];
        $data = $this->filter($option, 0);
        $default = [
            'page_name' => 'Khuyến mãi hot'
        ];
        $option = $this->sideBar();
        $option = array_merge($option, $default);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $page = $data['page'];
        $current_url = App::load('url')->current();
        $this->render('index', compact('data', 'option', 'current_url', 'page'));
    }

    public function manufacturer($manufacturer_id = '')
    { //nhan hang
        $model = $this->model();
        $alias = $model->alias();

        $manufacturer_id = explode('-', $manufacturer_id);
        $manufacturer_id = array_pop($manufacturer_id);

        $option = [
            'where' => "{$alias}.status > 0 AND extension.string_3 = " . $manufacturer_id
        ];
        $data = $this->filter($option, 0);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $service = App::load('manufacturer', 'service');
        $manufacturer = $service->getById($manufacturer_id);
        $manufacturer = current($manufacturer);

        $default = [
            'page_name' => $manufacturer['title'] ?? 'Nhan hang'
        ];
        $option = $this->sideBar();
        $option = array_merge($option, $default);

        $page = $data['page'];
        $current_url = App::load('url')->current();
        $this->render('index', compact('data', 'option', 'current_url', 'page'));
    }

    public function bestSellFromShop()
    { //best sales
        $model = $this->model();
        $alias = $model->alias();

        $option = [
            'where' => "{$alias}.status = 3"
        ];
        $data = $this->filter($option, 0);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $default = [
            'page_name' => 'Bán chạy từ shop'
        ];
        $option = $this->sideBar();
        $option = array_merge($option, $default);

        $page = $data['page'];
        $current_url = App::load('url')->current();
        $this->render('index', compact('data', 'option', 'current_url', 'page'));
    }

    public function hot()
    {//san pham hot
        $model = $this->model();
        $alias = $model->alias();

        $option = [
            'where' => "{$alias}.status = 2"
        ];
        $data = $this->filter($option);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $default = [
            'page_name' => 'Sản phẩm hot'
        ];
        $option = $this->sideBar();
        $option = array_merge($option, $default);

        $page = $data['page'];
        $current_url = App::load('url')->current();
        $this->render('index', compact('data', 'option', 'current_url', 'page'));
    }

    public function category($category = 0)
    {
        $seo = App::mp('seo');

        $request = App::mp('request');

        $service = App::category();

        $option['select'] = 'id, title, seo_id, parent_id';
        $categories = $service->extract($category, false, 'title', '', $option);

        if (empty($categories)) {
            abort('NotFoundException');
        }

        $model = $this->model();
        $alias = $model->alias();

        $categories = Hash::combine($categories, '{n}.category.id', '{n}.category');

        $category_id_list = array_keys($categories);
        $cats = implode(',', $category_id_list);

        $option = [
            'where' => "{$alias}.category_id IN (" . $cats . ')'
        ];
        $data = $this->filter($option);
        $option = $this->sideBar($categories, $category);

        $breadcrumb = [
            'category' => array_merge(['alias' => 'category'], $categories[$category])
        ];
        $this->set('breadcrumb', $breadcrumb);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $page = $data['page'];
        $current_url = App::load('url')->current();
        $this->render('index', compact('data', 'option', 'current_url', 'page', 'category_id_list'));
    }

    private function loadAjax($data = [])
    {
        $items = $data['list'] ?? [];
        $data = [
            'total' => $data['page']['total'] ?? 0,
            'current' => $data['page']['current'] ?? 0,
            'html' => $this->render('item', compact('items')),
        ];

        return $this->renderJson($data);
    }

    private function sideBar($categories = [], $category = 0)
    {
        // $this->associate($categories);

        // $tree = $this->formatProductCategory($categories);

        return [
            'categories' => $categories,
            'current_product_category' => $category,
            'page_name' => isset($categories[$category]['title']) ? $categories[$category]['title'] : ''
        ];
    }

    private function formatProductCategory($categories)
    {
        $categories = Hash::nest($categories, [
            'idPath' => '{n}.id',
            'parentPath' => '{n}.parent_id',
        ]);

        $categories = current($categories);

        return $categories;
    }

    private function filter($option = [])
    {
        $request = App::mp('request');
        $model = $this->model();
        $alias = $model->alias();

        if (empty($option['order'])) {
            $orders = [
                'lastest' => $alias . '.id desc',
                'price-asc' => $alias . '.price asc',
                'price-desc' => $alias . '.price desc'
            ];

            $order = empty($request->name['order']) ? 'lastest' : $request->name['order'];
            $order = $orders[$order];
        } else {
            $order = $option['order'];
        }

        if (empty($option['page'])) {
            $page = empty($request->name['page']) ? 1 : $request->name['page'];
        } else {
            $page = $option['page'];
        }

        $default = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id, extension.string_1 as promote, extension.string_4 as promote_start, extension.string_5 as promote_end",
            'order' => $order,
            'page' => $page,
            'limit' => $option['limit'] ?? 20,
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = ' . $alias . '.id AND extension.target_model ="' . $alias . '"'
                ],
            ],
            'paginator' => [
                'navigator' => false
            ]
        ];

        $default = array_merge($default, $option);

        $page = [];
        $data = $this->paginate($default, true, $page);

        if ($data['list']) {
            $today = date('Y-m-d');
            foreach ($data['list'] as $id => &$item) {
                $item = array_merge($item['product'], $item['extension']);
                unset($item['extension']);

                $model->promotion($item);
            }

            $this->associate($data['list']);
        }

        $data['page'] = $page;
        $data['order'] = $order;

        return $data;
    }

    public function rating($id = 0)
    {
        $pf = App::load('productRating', 'model');
        $pf->init($pf->field());
        $model = [
            'product-rating' => $pf,
        ];

        $option = [
            'select' => 'id, created',
            'where' => 'string_5 = 1',
            'order' => 'id desc',
            'limit' => 50
        ];

        $list = App::load('extension', 'service')->get($id, $model, $option);
        $this->render('rating', compact('list'));
    }

    public function vote()
    {
        $request = App::mp('request');
        $data = [
            'fullname' => $request->data['fullname'],
            'email' => $request->data['email'],
            'price' => $request->data['price'],
            'content' => $request->data['content'],
            'quantity' => $request->data['quantity'],
            'shipping' => $request->data['shipping'],
            'target_id' => $request->data['target'],
            'target_model' => 'product-rating',
            'status' => 0,
        ];

        $service = App::load('extension', 'service', ['productRating', 'extension', 'rating']);
        $service->model()->init($service->model()->field());
        $service->save($data);

        return true;
    }

    public function ask()
    {
        $request = App::mp('request');

        $data = [
            'fullname' => $request->data['fullname'],
            'email' => $request->data['email'],
            'category' => $request->data['category'],
            'private' => isset($request->data['private']) ? $request->data['private'] : 0,
            'question' => $request->data['question'],
            'status' => 0,
            'target_id' => $request->data['target'],
            'target_model' => 'product-faq'
        ];

        $service = App::load('extension', 'service', ['productFaq', 'extension', 'faq']);
        $service->model()->init($service->model()->field());
        $service->save($data);

        return true;
    }

    public function faq($id = 0)
    {
        $pf = App::load('productFaq', 'model');
        $pf->init($pf->field());
        $model = [
            'product-faq' => $pf,
        ];

        $option = [
            'select' => 'id, created',
            'where' => 'string_4 = 0 AND string_5 = 1',
            'order' => 'id desc',
            'limit' => 50
        ];

        $list = App::load('extension', 'service')->get($id, $model, $option);
        $this->render('faq', compact('list'));
    }
}
