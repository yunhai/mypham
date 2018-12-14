<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Lib\Session;
use Mp\Lib\Utility\Text;
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
            case 'filter':
                $this->filter();
                break;
            default:
                parent::navigator();
                break;
        }
    }

    public function index()
    {
        $category = $this->model()->category();
        $category_id = current(array_keys($category)); 
        $this->category($category_id);
    }

    public function detail($id = 0)
    {
        $alias = $this->model()->alias();

        $where = "{$alias}.id = {$id} AND {$alias}.status > 0";

        $target = $this->model()->find(compact('where'), 'first');
        if (empty($target)) {
            abort('NotFoundException');
        }

        $product_history = Session::read('product_history');
        $product_history[$id] = $id;
        Session::write('product_history', $product_history);

        $target = $target[$alias];

        $this->model->checkPromotion($target, false);
        $service = App::load('file', 'service');

        if (!empty($target['gallery'])) {
            $target['gallery'] = array_map(function ($item) {
                return [
                    'id' => $item,
                    'file_id' => $item,
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
        $filter = $this->generateFilterBar($category);
        $breadcrumb = [
            $category,
            $target,
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

        $target['faq_count'] = $this->countFaq($id);
        $target = array_merge($target, $this->countRating($id));

        $target['start'] = $target['rating_point'];
        $target['comment'] = $target['faq_count'];


/*
    [display_mode] => 2
    [default_mode] => 5c0b0631b94f2

    'display_mode' => [
        '1' => 'Hình ảnh',
        '2' => 'Textbox'
    ], 

    if display_mode == 1: display like PRODUCT-Detail-Mỹ phẩm
    if display_mode == 1: display like PRODUCT-Detail-THỜI TRANG - dropdown list

    default_mode -> active property child item.
*/
        $this->detailSideBar($target);
        $last_view_product = $this->getByIdList($product_history);
// echo '<pre>';
// var_dump($last_view_product);exit;
        $this->render('detail', compact('target', 'option', 'manufacturer', 'last_view_product', 'filter'));
    }

    protected function countFaq($id)
    {
        $pf = App::load('productFaq', 'model');
        $pf->init($pf->field());
        $model = [
            'product-faq' => $pf,
        ];

        $option = [
            'where' => 'string_4 = 0 AND string_5 = 1',
            'order' => 'id desc',
        ];

        $faq = App::load('extension', 'service')->count($id, $model, $option);

        return $faq[0][0]['count'] ?? 0;
    }

    protected function countRating($id)
    {
        $pf = App::load('productRating', 'model');
        $pf->init($pf->field());
        $model = [
            'product-rating' => $pf,
        ];

        $option = [
            'select' => 'id',
            'where' => 'string_5 = 1',
            'order' => 'id desc',
            'limit' => 1000,
        ];

        $rating = App::load('extension', 'service')->get($id, $model, $option);

        $rating_count = 0;
        $rating_point = 0;
        if ($rating) {
            foreach ($rating as $item) {
                $rating_point += $item['price'] ?? 0;
                $rating_point += $item['quantity'] ?? 0;
                $rating_point += $item['shipping'] ?? 0; 
                $rating_count++;
            }
            $rating_point = ceil($rating_point / ($rating_count * 3));
        }
       
        return compact('rating_count', 'rating_point');
    }

    private function detailSideBar($target)
    {
        $service = App::load('product', 'service');
        $manufacturer_id = $target['manufacturer_id'];

        $promotion_list = $service->promote(4);
        $best_selling_list = $service->bestSelling(4);
        $same_manufacturer_list = $this->byManufacturer($manufacturer_id);

        $this->set('sidebar', compact('same_manufacturer_list', 'promotion_list', 'best_selling_list'));
    }

    private function byManufacturer(int $manufacturer_id, $limit = 8)
    {
        $alias = $this->model()->alias();

        $query = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id",
            'where' => 'extension.string_3 = ' . $manufacturer_id,
            'order' => 'extension.string_3 desc',
            'limit' => $limit,
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = ' . $alias . '.id  AND extension.target_model = "' . $alias . '"'
                ],
            ]
        ];

        $result = [];
        $result = $this->model()->find($query);

        $result = Hash::combine($result, '{n}.product.id', '{n}.product');

        $this->model()->checkPromotion($result);
        $this->associate($result);

        return $result;
    }

    private function getByIdList($id_list) {
        $id_list = array_filter($id_list);

        if (empty($id_list)) {
            return [];
        }

        $alias = $this->model()->alias();

        $query = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.category_id, {$alias}.file_id, {$alias}.seo_id",
            'where' => "{$alias}.id IN (" . implode(',', $id_list) . ')',
            'order' => 'extension.string_4 desc',
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = ' . $alias . '.id  AND extension.target_model = "' . $alias . '"'
                ],
            ]
        ];

        $result = [];
        $result = $this->model()->find($query);

        $result = Hash::combine($result, '{n}.product.id', '{n}.product');
        $this->model()->checkPromotion($result);
        $this->associate($result);

        return $result;
    }

    private function generateFilterBar($category = 0) {
        $filter = App::mp('config')->get('product.filter_mode');
        
        $model = App::load('manufacturer', 'model');
        $model->category(App::category()->flat('manufacturer'));

        $fashion_category = $this->getFashionCategoryId();
        
        $tmp = NON_FASHION_BRANCH;
        if (in_array($category, $fashion_category)) {
            $tmp = FASHION_BRANCH;
            unset($filter['skin']);
            unset($filter['state']);
        }

        $select = 'manufacturer.id, manufacturer.title, manufacturer.category_id';
        $where = 'manufacturer.status > 0 AND category_id = ' . $tmp;
        $manufacturer = $model->find(compact('select', 'where'));
        $manufacturer = Hash::combine($manufacturer, '{n}.manufacturer.id', '{n}.manufacturer.title', '{n}.manufacturer.category_id');

        $filter['manufacturer'] = $manufacturer ? current($manufacturer) : [];

        return $filter;
    }

    private function getFashionCategoryId() {
        $option = [
            'select' => 'id, title',
        ];
        $categories = App::category()->extract(PRODUCT_CATEGORY_FASHION, false, 'title', '', $option);
        return Hash::combine($categories, '{n}.category.id', '{n}.category.id');
    }

    public function category($category = 0)
    {
        $request = App::mp('request');
        $model = $this->model();
        $alias = $model->alias();

        $option['select'] = 'id, title, seo_id, parent_id, slug';
        $categories = App::category()->extract($category, false, 'title', '', $option);

        if (empty($categories)) {
            abort('NotFoundException');
        }

        $categories = Hash::combine($categories, '{n}.category.id', '{n}.category');

        $category_id_list = array_keys($categories);
        $cats = implode(',', $category_id_list);

        $option = [
            'where' => "{$alias}.category_id IN (".$cats.')',
        ];
        $data = $this->makeFilter($option);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $filter = $this->generateFilterBar($category);
        $category = array_shift($categories);


        $breadcrumb = [
            $category,
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => $category['title'],
            'category' => $category,
            'category_id' => $category['id'],
            'page' => $data['page'],
            'current_url' => App::load('url')->current(),
        ];

        $product_history = Session::read('product_history');
        $last_view_product = $product_history ? $this->getByIdList($product_history) : [];

        $this->render('index', compact('data', 'option', 'filter', 'last_view_product', 'category'));
    }

    private function banner()
    {
        $service = App::load('banner', 'service');
        $slider = $service->getByCategorySlug('home-slideshow');

        return compact('slider', 'header');
    }

    public function filter()
    {
        $request = App::mp('request');
        $param = $request->param;

        $category = 0;
        if (!empty($param['category'])) {
            $tmp = explode('-', $param['category']);
            $category = array_pop($tmp);
        }

        $map_list = App::mp('config')->get('product.filter');

        $map_field = [
            'price' => 'price',
            'state' => 'string_7',
            'skin' => 'string_6',
            'manufacturer' => 'string_3'
        ];

        $condition = array_intersect_key($param, $map_field);

        $alias = $this->model()->alias();
        $subwhere = '';
        if ($condition) {            
            $subcondition = [];
            foreach ($condition as $key => $value) {
                switch ($key) {
                    case 'price':
                        $field = "{$alias}.{$map_field[$key]}";
                        $range = $map_list[$key][$value];
                        switch ($value) {
                            case 'nho-hon-100000':
                                $string = "{$field} < {$range}";
                                break;
                            case 'lon-hon-500000':
                                $string = "{$field} > {$range}";
                                break;
                            default:
                                list($min, $max) = explode('-', $range);
                                --$max;
                                $string = "{$field} BETWEEN ({$min} AND {$max})";
                                break;
                        }
                        break;
                    default:
                        $field = "extension.{$map_field[$key]}";
                        $value = $map_list[$key][$value] ?? $value;
                        $string = "{$field} = {$value}";
                        break;
                }
                

                array_push($subcondition, $string);
            }
            $subwhere = ' AND ' . implode(' AND ', $subcondition);
        }

        $option = [
            'where' => "{$alias}.status > 0 {$subwhere} ",
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = '.$alias.'.id AND extension.target_model ="'.$alias.'"',
                ],
            ],
        ];

        $search = [
            'keyword' => '',
            'param' => $param,
        ];

        $page = $data['page'] ?? 1;

        $data = $this->makeFilter($option);
        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $filter = $this->generateFilterBar($category);

        $option = [
            'select' => 'category.id, title, seo_id, parent_id, slug'
        ];
        $categories = App::category()->extract($category, false, 'title', '', $option);

        if (empty($categories)) {
            abort('NotFoundException');
        }

        $categories = Hash::combine($categories, '{n}.category.id', '{n}.category');

        $category = array_shift($categories);
        $product_history = Session::read('product_history');
        $last_view_product = $product_history ? $this->getByIdList($product_history) : [];

        $breadcrumb = [
            ['title' => 'Tìm kiếm'],
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => 'Tìm kiếm',
            'search' => $search,
            'page' => $page,
        ];

        $banner = $this->banner();
        $slider_banner = $banners['slider'] ?? [];

        $this->render('index', compact('data', 'option', 'filter', 'last_view_product', 'category', 'slider_banner'));
    }
//////////////////

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

    private function makeFilter($option = [])
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
                'navigator' => false,
            ],
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
                if (0 === mb_strpos($value, 'token:')) {
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
            $match  = 1;
            $option = [
                'select' => 'id, target_id',
                'where' => $match.' AND target_model = "'.$alias.'"',
                'limit' => '1000',
            ];

            $data = [];
            $tmp = $model->find($option, 'all', 'target_id');
            if ($tmp) {
                if (empty($category)) {
                    $category = implode(',', array_keys($this->model()->category()));
                }

                $id = implode(',', array_keys($tmp));
                $option = [
                    'where' => "{$alias}.status > 0 AND {$alias}.id IN (".$id.") AND {$alias}.category_id IN ({$category})",
                ];
                $data = $this->makeFilter($option);
            }
            $search = [
                'category' => $category,
                'keyword' => $keyword,
            ];

            $page = $data['page'] ?? 1;
        }

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $breadcrumb = [
            ['title' => 'Tìm kiếm'],
        ];
        $this->set('breadcrumb', $breadcrumb);
        $this->set('search', $request->data);

        $option = [
            'page_title' => "Tìm kiếm [{$keyword}]",
            'search' => $search,
            'page' => $page,
            'current_url' => App::load('url')->current().'/token:'.$token,
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

        if (false === empty($tmp)) {
            $category = implode(',', array_keys($tmp));
        }

        $select = "{$alias}.id, {$alias}.title, {$alias}.price, {$alias}.file_id, {$alias}.seo_id";
        $where = "{$alias}.id <> {$id} AND {$alias}.status > 0 AND {$alias}.category_id IN ({$category})";
        $order = "{$alias}.id desc";
        $limit = 8;

        $others = $this->model()->find(compact('select', 'where', 'limit', 'order'));

        $others = Hash::combine($others, '{n}.'.$alias.'.id', '{n}.'.$alias);
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
                    'condition' => 'extension.target_id = '.$alias.'.id AND extension.target_model ="'.$alias.'"',
                ],
            ],
        ];
        $data = $this->makeFilter($option);
        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $breadcrumb = [
            ['title' => 'Khuyến mãi'],
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => 'Khuyến mãi',
            'page' => $data['page'],
            'current_url' => App::load('url')->current(),
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
        $data = $this->makeFilter($option);
        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $breadcrumb = [
            ['title' => 'Sản phẩm bán chạy'],
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => 'Sản phẩm bán chạy',

            'page' => $data['page'],
            'current_url' => App::load('url')->current(),
        ];

        $this->sideBar('best_selling');
        $this->render('index', compact('data', 'option'));
    }

    private function sideBar($current = '', $category_id = 0)
    {
        $service = App::load('product', 'service');
        
        if ('best_selling' == $current) {
            $sidebar_product = $service->promote(4);
            $sidebar_product_block_name = 'Sản phẩm khuyến mãi';
        } else {
            $sidebar_product = $service->bestSelling(4);
            $sidebar_product_block_name = 'Sản phẩm bán chạy';
        }

        if (!$sidebar_product) {
            $sidebar_product = $service->lastest(4);
            $sidebar_product_block_name = 'Sản phẩm mới nhất';
        }
        
        $manumanufacturer_id_list = Hash::combine($sidebar_product, '{n}.manufacturer_id', '{n}.manufacturer_id');
        $manumanufacturers = $this->getManufacturerList($manumanufacturer_id_list);
        $this->associate($manumanufacturers);
        foreach ($sidebar_product as &$item) {
            $item['manufacturer'] = $manumanufacturers[$item['manufacturer_id']];
        }
        
        if (!$category_id) {
            $category_id = $this->model()->category();
            $category_id = array_shift($category_id);
        }
    
        $sidebar = [
            'product' => $sidebar_product,
            'sidebar_product_block_name' => $sidebar_product_block_name,
            'category_id' => $category_id,
        ];

        $this->set('sidebar', $sidebar);
    }
    
    private function getManufacturerList(array $id_list = [])
    {
        $manufacturer_id = implode(',', $id_list);
        $model = App::load('manufacturer', 'model');
        $model->category(App::category()->flat('manufacturer'));

        $select = 'manufacturer.id, manufacturer.title, manufacturer.seo_id';
        $where = "manufacturer.id IN ({$manufacturer_id}) AND manufacturer.status > 0";

        $manufacturer = $model->find(compact('select', 'where'), 'all');
        return Hash::combine($manufacturer, '{n}.manufacturer.id', '{n}.manufacturer');
    }

    public function manufacturer($manufacturer_id = '')
    {
        $model = $this->model();
        $alias = $model->alias();

        $manufacturer_id = explode('-', $manufacturer_id);
        $manufacturer_id = array_pop($manufacturer_id);

        $option = [
            'where' => "{$alias}.status > 0 AND extension.string_3 = ".$manufacturer_id,
            'join' => [
                [
                    'table' => 'extension',
                    'alias' => 'extension',
                    'type' => 'INNER',
                    'condition' => 'extension.target_id = '.$alias.'.id AND extension.target_model ="'.$alias.'"',
                ],
            ],
        ];
        $data = $this->makeFilter($option);

        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $data = $this->makeFilter($option);
        if ($this->isAjax()) {
            return $this->loadAjax($data);
        }

        $service = App::load('manufacturer', 'service');
        $manufacturer = $service->getById($manufacturer_id);
        $manufacturer = current($manufacturer);

        $manufacturer = 'Nhãn hàng '.($manufacturer['title'] ?? '');

        $breadcrumb = [
            ['title' => $manufacturer],
        ];
        $this->set('breadcrumb', $breadcrumb);

        $option = [
            'page_title' => $manufacturer,
            'page' => $data['page'],
            'current_url' => App::load('url')->current(),
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
            'limit' => 50,
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
            'target_model' => 'product-faq',
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
            'limit' => 200,
        ];

        $list = App::load('extension', 'service')->get($id, $model, $option);
        $this->render('faq', compact('list'));
    }
}
