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
            case 'best_selling':
                $this->bestSelling();
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

    public function detail($id = 0)
    {
        $alias = $this->model()->alias();

        $where = "{$alias}.id = {$id} AND {$alias}.status > 0";

        $target = $this->model()->find(compact('where'), 'first');
        if (empty($target)) {
            abort('NotFoundException');
        }

        $target = $target[$alias];

        $this->model->checkPromotion($target, false);
        $service = App::load('file', 'service');

        if (!empty($target['gallery'])) {
            $target['gallery'] = array_map(function ($item) {
                return [
                    'id' => $item,
                    'file_id' => $item
                ];
            }, explode(',', $target['gallery']));
            $this->associate($target['gallery']);
        }

        $option = [];
        $option['others'] = $this->other($target);

        $service = App::category();
        $category = $service->tree('product', ['select' => 'id, title, seo_id', 'where' => 'status > 0']);
        $option['category'] = $category;
        array_shift($option['category']);
        $option['category'] = Hash::combine($category, '{n}.id', '{n}.title');

        $category = $category[$target['category_id']];

        $breadcrumb = [
            $category,
            $target
        ];
        $this->set('breadcrumb', $breadcrumb);

        $manufacturer_id = $target['manufacturer_id'];
        $model = App::load('manufacturer', 'model');
        $model->category(App::category()->flat('manufacturer'));

        $select = 'manufacturer.id, manufacturer.title, manufacturer.seo_id';
        $where = "manufacturer.id = {$manufacturer_id} AND manufacturer.status > 0";

        $manufacturer = $model->find(compact('select', 'where'), 'first');
        $manufacturer = current($manufacturer);

        $target['manufacturer_target'] = $manufacturer;
        $this->associate($manufacturer);
        $this->associate([$target]);

        if (isset($target['property'])) {
            $detail_files = Hash::combine($target['property'], '{s}.detail.{s}.file_id', '{s}.detail.{s}.file_id');
            $this->refer(['file' => $detail_files]);
        }

        $this->sideBar('detail', $target['category_id']);

        $this->render('detail', compact('target', 'option', 'manufacturer'));
    }

    public function category($category = 0)
    {
        $request = App::mp('request');
        $model = $this->model();
        $alias = $model->alias();

        $option['select'] = 'id, title, seo_id, parent_id';
        $categories = App::category()->extract($category, false, 'title', '', $option);

        if (empty($categories)) {
            abort('NotFoundException');
        }

        $categories = Hash::combine($categories, '{n}.category.id', '{n}.category');

        $category_id_list = array_keys($categories);
        $cats = implode(',', $category_id_list);

        $option = [
            'where' => "{$alias}.category_id IN (" . $cats . ')'
        ];
        $data = $this->filter($option);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $category = array_shift($categories);

        $breadcrumb = [
            $category
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => $category['title'],
            'category' => $category,
            'category_id' => $category['id'],
            'page' => $data['page'],
            'current_url' => App::load('url')->current()
        ];

        $this->sideBar('category', $category['id']);
        $this->render('index', compact('data', 'option'));
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

    private function filter($option = [])
    {
        $request = App::mp('request');
        $model = $this->model();
        $alias = $model->alias();

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $default = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id",
            'order' => $alias . '.id desc',
            'page' => $page,
            'limit' => $option['limit'] ?? 20,
            'paginator' => [
                'navigator' => false
            ]
        ];

        $default = array_merge($default, $option);

        $page = [];
        $data = $this->paginate($default, true, $page);
        $data['list'] = Hash::combine($data['list'], '{n}.product.id', '{n}.product');

        if ($data['list']) {
            $model->checkPromotion($data['list']);
            $this->associate($data['list']);
        }

        $data['page'] = $page;

        return $data;
    }

    private function appApi()
    {
        $model = new \Mp\Model\Apps();

        return $model->api(App::load('login')->targetId());
    }

    public function search()
    {
        $request = App::mp('request');

        $keyword = $category = $token = $search = '';
        $page = 1;
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
        } else {
            $page = $data = $search = [];
        }

        if (!empty($keyword)) {
            $alias = $this->model()->alias();

            $model = new \Mp\Model\Search();

            $index = 0;
            $keyword = Text::slug($keyword, '');
            $keywordArray = explode(' ', $keyword);

            $match = "keyword LIKE '%{$keyword}%'";
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
                ];
                $data = $this->filter($option);
            }
            $search = [
                'category' => $category,
                'keyword' => $keyword
            ];

            $page = $data['page'] ?? 1;
        }

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $breadcrumb = [
            ['title' => 'Tìm kiếm']
        ];
        $this->set('breadcrumb', $breadcrumb);
        $this->set('search', $request->data);

        $option = [
            'page_title' => "Tìm kiếm [{$keyword}]",
            'search' => $search,
            'page' => $page,
            'current_url' => App::load('url')->current() . '/token:' . $token
        ];

        $this->sideBar('search');
        $this->render('index', compact('data', 'option'));
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
        $limit = 8;

        $others = $this->model()->find(compact('select', 'where', 'limit', 'order'));

        $others = Hash::combine($others, '{n}.' . $alias . '.id', '{n}.' . $alias);
        $this->associate($others);
        $this->model()->checkPromotion($others);

        return $others;
    }

    public function promote()
    {
        $model = $this->model();
        $alias = $model->alias();

        $option = [
            'where' => "{$alias}.status > 0 AND CURDATE() BETWEEN extension.string_4 AND extension.string_5",
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = ' . $alias . '.id AND extension.target_model ="' . $alias . '"'
                ],
            ],
        ];
        $data = $this->filter($option);
        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $breadcrumb = [
            ['title' => 'Khuyến mãi']
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => 'Khuyến mãi',

            'page' => $data['page'],
            'current_url' => App::load('url')->current()
        ];

        $this->sideBar();
        $this->render('index', compact('data', 'option'));
    }

    public function bestSelling()
    {
        $model = $this->model();
        $alias = $model->alias();

        $option = [
            'where' => "{$alias}.status = 2",
        ];
        $data = $this->filter($option);
        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $breadcrumb = [
            ['title' => 'Sản phẩm bán chạy']
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => 'Sản phẩm bán chạy',

            'page' => $data['page'],
            'current_url' => App::load('url')->current()
        ];

        $this->sideBar('best_selling');
        $this->render('index', compact('data', 'option'));
    }

    private function sideBar($current = '', $category_id = 0)
    {
        $service = App::load('product', 'service');

        if ($current == 'best_selling') {
            $sidebar_product = $service->promote(3);
            $sidebar_product_block = 'Sản phẩm khuyến mãi';
        } else {
            $sidebar_product = $service->bestSelling(3);
            $sidebar_product_block = 'Sản phẩm bán chạy';
        }

        if (!$sidebar_product) {
            $sidebar_product = $service->lastest(3);
            $sidebar_product_block = 'Sản phẩm mới nhất';
        }

        if (!$category_id) {
            $category_id = $this->model()->category();
            $category_id = array_shift($category_id);
        }

        $sidebar = [
            'product' => $sidebar_product,
            'product_block' => $sidebar_product_block,
            'category_id' => $category_id
        ];

        $this->set('sidebar', $sidebar);
    }

    public function manufacturer($manufacturer_id = '')
    {
        $model = $this->model();
        $alias = $model->alias();

        $manufacturer_id = explode('-', $manufacturer_id);
        $manufacturer_id = array_pop($manufacturer_id);

        $option = [
            'where' => "{$alias}.status > 0 AND extension.string_3 = " . $manufacturer_id,
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = ' . $alias . '.id AND extension.target_model ="' . $alias . '"'
                ],
            ],
        ];
        $data = $this->filter($option);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $data = $this->filter($option);
        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $service = App::load('manufacturer', 'service');
        $manufacturer = $service->getById($manufacturer_id);
        $manufacturer = current($manufacturer);

        $manufacturer = 'Nhãn hàng ' . ($manufacturer['title'] ?? '');

        $breadcrumb = [
            ['title' => $manufacturer]
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => $manufacturer,
            'page' => $data['page'],
            'current_url' => App::load('url')->current()
        ];

        $this->sideBar();
        $this->render('index', compact('data', 'option'));
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
            'limit' => 200
        ];

        $list = App::load('extension', 'service')->get($id, $model, $option);
        $this->render('faq', compact('list'));
    }
}
