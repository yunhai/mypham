<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Lib\Utility\Text;

use Mp\Controller\Backend\Product;

class ProductController extends Product
{
    public function __construct($model = 'product', $table = 'product', $alias = 'product', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);

        $virtualField = [
            'string_1' => 'promote',
            'string_2' => 'store_id',
            'string_3' => 'manufacturer_id',
            'string_4' => 'promote_start',
            'string_5' => 'promote_end',
            'text_1' => [
                'point',
                'weight',
                'capacity',
                'option',
                'option_price',
                'option_promotion',
                'shipping',
                'code',
                'gallery',
                'files'
            ]
        ];

        $this->model()->loadExtension(new \Mp\Model\Extension());
        $this->model()->virtualField($virtualField);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'faq':
                $act = isset($request->query[2]) ? $request->query[2] : '';

                switch ($act) {
                    case 'update':
                        $this->updateFaq($request->query[3]);
                        break;
                    case 'delete':
                        $this->deleteFaq();
                        break;
                    case 'edit':
                        $this->editFaq($request->query[3]);
                        break;
                    case 'filter':
                        $this->filter('makeFaqFilter');
                        break;
                    default:
                        if (isset($request->query[3]) && $request->query[3] == 'filter') {
                            $this->filter('makeFaqFilter');
                        } else {
                            $this->indexFaq($request->query[2]);
                        }
                        break;
                }
                break;
            case 'rating':
                $act = isset($request->query[2]) ? $request->query[2] : '';

                switch ($act) {
                    case 'update':
                        $this->updateRating($request->query[3]);
                        break;
                    case 'delete':
                        $this->deleteRating();
                        break;
                    default:
                        $this->indexRating($request->query[2]);
                        break;
                }
                break;
            default:
                parent::navigator();
                break;
        }
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
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.price, {$alias}.category_id, {$alias}.modified, {$alias}.created",
            'where' => $where,
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);

        $f = '{n}.' . $alias . '.id';
        $id = Hash::combine($data['list'], $f, $f);
        $data['association'] = $this->countFaqRating($id);
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

    public function updateRating($status = 0)
    {
        $request = App::mp('request');

        $map = $this->status();
        $alias = $this->model()->alias();
        if (isset($map[$status]) && !empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productRating', 'extension', 'productRating']);
            $f = array_search('status', $service->model()->field());
            $fields = [
                $f => $status
            ];

            $service->update($fields, $request->data[$alias]);
        }

        $this->flash('edit', __('m0003', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function deleteRating()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productRating', 'extension', 'productRating']);
            $service->delete($request->data[$alias]);
        }

        $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function indexRating($id = 0)
    {
        $request = App::mp('request');

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => 'id, created',
            'page' => $page,
        ];

        $pf = App::load('productRating', 'model');
        $pf->init($pf->field());
        $model = [
            'product-rating' => $pf,
        ];

        $data = App::load('extension', 'service')->paginate($model, $option, $id);

        $this->render('rating/index', compact('data'));
    }

    public function editFaq($id = 0)
    {
        $request = App::mp('request');
        $id = (int) $id;

        $service = App::load('extension', 'service', ['productFaq', 'extension', 'faq']);
        $service->model()->init($service->model()->field());

        $target = $service->detail($id);

        if (empty($target)) {
            abort('NotFoundException');
        }

        list($model) = explode('-', $target['target_model']);
        $model = App::load($model, 'model');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $error = [];

            $flag = $service->save($request->data[$alias], $error);

            if ($flag) {
                $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');
            } else {
                $error = [
                    $alias => $error[$service->model()->alias()]
                ];

                $this->set('error', $error);
                $this->flash('edit', __('m0002', 'Please review your data.'), 'error');
            }

            $target = array_merge($target, $request->data[$alias]);
        }

        $back_url = $this->model()->alias() . '/faq/' . $target['target_id'];
        $option = [
            'category' => $this->model()->category()
        ];

        return $this->render('faq/input', compact('target', 'option', 'back_url'));
    }

    public function indexFaq($id = 0)
    {
        $request = App::mp('request');

        $id = (int) $id;
        if (empty($id)) {
            $this->back();
        }

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => 'id, created',
            'page' => $page
        ];

        $pf = App::load('productFaq', 'model');
        $pf->init($pf->field());

        $model = [
            'product-faq' => $pf,
        ];

        $service = App::load('extension', 'service');
        $data = $service->paginate($model, $option);

        $category = $this->model()->category();
        $option = [
            'category' => $category,
            'filter' => [
                'target' => $id,
                'alias' => $this->model()->alias(),
                'category' => $category
            ]
        ];

        $this->render('faq/index', compact('data', 'option'));
    }

    public function makeFaqFilter($criteria = [], $token = '', $filter = '')
    {
        $request = App::mp('request');

        $id = (int) $id;
        if (empty($id)) {
            $this->back();
        }

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => 'id, created',
            'page' => $page
        ];

        $pf = App::load('productFaq', 'model');
        $pf->init($pf->field());

        $model = [
            'product-faq' => $pf,
        ];

        $service = App::load('extension', 'service');
        $data = $service->paginate($model, $option);

        $category = $this->model()->category();
        $option = [
            'category' => $category,
            'filter' => [
                'target' => $id,
                'alias' => $this->model()->alias(),
                'category' => $category
            ]
        ];

        $this->render('faq/index', compact('data', 'option'));
    }

    public function updateFaq($status = 0)
    {
        $request = App::mp('request');

        $map = $this->status();
        $alias = $this->model()->alias();
        if (isset($map[$status]) && !empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productFaq', 'extension', 'faq']);

            $f = array_search('status', $service->model()->field());
            $fields = [
                $f => $status
            ];

            $service->update($fields, $request->data[$alias]);
        }

        $this->flash('edit', __('m0003', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function deleteFaq()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        if (!empty($request->data[$alias])) {
            $service = App::load('extension', 'service', ['productFaq', 'extension', 'faq']);
            $service->delete($request->data[$alias]);
        }

        $this->flash('edit', __('m0001', 'Your data have been saved.'), 'success');

        return $this->back();
    }

    public function status($alias = '')
    {
        $request = App::mp('request');
        $special = ['faq', 'rating'];

        $status = App::mp('config')->get('status');

        if (in_array($request->query['action'], $special)) {
            return $status['default'];
        }

        if (empty($status[$alias])) {
            return $status['default'];
        }

        return $status[$alias];
    }

    public function index()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.status, {$alias}.price, {$alias}.category_id, {$alias}.modified, {$alias}.created",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);

        $f = '{n}.' . $alias . '.id';
        $id = Hash::combine($data['list'], $f, $f);
        $data['association'] = $this->countFaqRating($id);

        $data['category'] = $this->model()->category();

        $option = array_merge(
            $option,
            [
                'filter' => [
                    'alias' => $alias,
                    'category' => App::category()->flat($alias, false, 'title', '&nbsp;&nbsp;&nbsp;')
                ]
            ]
        );

        $this->render('index', compact('data', 'option'));
    }

    public function countFaqRating($id = [])
    {
        $count = [];
        if ($id) {
            $id = implode(',', $id);
            $select = 'target_id, string_5, target_model';
            $where = "target_id IN ({$id}) AND target_model IN ('product-faq', 'product-rating')";
            $order = 'target_model desc';

            $tmp = $this->model()->raw(compact('select', 'where', 'order'));

            $count = [];
            foreach ($tmp as $item) {
                $item = $item['extension'];
                $id = $item['target_id'];
                if (empty($count[$id])) {
                    $count[$id] = [
                        'product-faq' => [
                            'pending' => 0,
                            'all' => 0
                        ],
                        'product-rating' => [
                            'pending' => 0,
                            'all' => 0
                        ]
                    ];
                }

                $key = $item['target_model'];

                $count[$id][$key]['all']++;
                $count[$id][$key]['pending'] += (int) (empty($item['string_5']));
            }
        }

        return $count;
    }

    public function lastCheck(&$data = [])
    {
        parent::lastCheck($data);

        $alias = $this->model()->alias();

        // if (!empty($data[$alias]['option'])) {
        //     $data[$alias]['option'] = explode(',', $data[$alias]['option']);
        // }

        if (!empty($data[$alias]['promote_start'])) {
            @list($day, $month, $year) = explode('/', $data[$alias]['promote_start']);
            $data[$alias]['promote_start'] = sprintf('%0004d-%02d-%02d', $year, $month, $day);
        }

        if (!empty($data[$alias]['promote_end'])) {
            @list($day, $month, $year) = explode('/', $data[$alias]['promote_end']);
            $data[$alias]['promote_end'] = sprintf('%0004d-%02d-%02d', $year, $month, $day);
        }

        if (!empty($data['product-gallery'])) {
            $data[$alias]['gallery'] = implode(',', array_keys($data['product-gallery']));
        }

        if (!empty($data['product-files'])) {
            $data[$alias]['files'] = implode(',', array_keys($data['product-files']));
        }
    }

    public function formatPostData($data = [], $alias = '', $map = [])
    {
        $main = $data[$alias];
        $map = [
            $alias . '-files' => 'files',
            $alias . '-gallery' => 'gallery',
        ];
        foreach ($map as $key => $target) {
            if (isset($data[$key])) {
                $tmp = Hash::combine($data[$key], '{n}.id', '{n}.id');

                $tmp = array_filter($tmp);
                if ($tmp) {
                    $main[$target] = implode(',', $tmp);
                }

                unset($data[$key]);
            }
        }

        $result = [
            $alias => $main
        ];
        foreach ($data as $f => $attr) {
            if ($f == $alias) {
                continue;
            }
            $result[$f] = $attr;
        }

        return $result;
    }

    public function edit($id = 0)
    {
        $request = App::mp('request');
        $alias = $this->model()->alias();

        $id = (int) $id;
        $fields = "{$alias}.id, {$alias}.title, {$alias}.category_id, {$alias}.idx, {$alias}.price, {$alias}.content, {$alias}.status, {$alias}.seo_id, {$alias}.file_id";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        $tmp = ['promote_start', 'promote_end'];
        foreach ($tmp as $f) {
            if (empty($target[$alias][$f])) {
                $target[$alias][$f] = '';
            } else {
                $target[$alias][$f] = date('d/m/Y', strtotime($target[$alias][$f]));
            }
        }

        $target = array_merge($target, App::mp('seo')->target($target[$alias]['seo_id']));

        if (!empty($request->data[$alias])) {
            $error = [];
            $request->data[$alias] = $this->formatData($request->data[$alias]);
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
            'status' => $this->status($alias),
            'category' => $this->getCategory($alias, true, 'title', '&nbsp;&nbsp;&nbsp;&nbsp;')
        ];

        return $this->render('input', compact('target', 'option'));
    }

    public function attach(&$target = [], $alias = '', $fields = ['file' => 'file_id'])
    {
        $files = empty($target[$alias]['file_id']) ? [] : [$target[$alias]['file_id']];

        $fields = [
            'gallery' => 'gallery',
            'files' => 'files',
            'file' => 'file_id'
        ];
        foreach ($fields as $name => $field) {
            if (empty($target[$alias][$field])) {
                $target[$alias][$field] = [];
                continue;
            }

            $target[$name] = explode(',', $target[$alias][$field]);
            $files = array_merge($files, $target[$name]);
        }
        $this->refer(['file' => $files]);

        $storeId = $target[$alias]['store_id'];
        if ($storeId) {
            $tmp = App::load('store', 'service')->getById($storeId);
            if ($tmp) {
                $tmp[$storeId]['json'] = json_encode($tmp[$storeId], true);
            }

            $target['store'] = $tmp;
        }

        $manufacturerId = $target[$alias]['manufacturer_id'];
        if ($manufacturerId) {
            $tmp = App::load('manufacturer', 'service')->getById($manufacturerId);
            if ($tmp) {
                $tmp[$manufacturerId]['json'] = json_encode($tmp[$manufacturerId], true);
            }

            $target['manufacturer'] = $tmp;
        }
    }

    public function save($data = [], &$error = [], $validator = true)
    {
        $alias = $this->model()->alias();

        $this->lastCheck($data);

        if ($validator) {
            $flag = $this->validate($alias, $data[$alias], $error);
            if (!$flag) {
                $error = [
                    $alias => $error
                ];

                return false;
            }
        }

        $this->model()->begin();
        $this->model()->save($data[$alias]);

        if (empty($data[$alias]['id'])) {
            $data[$alias]['id'] = $this->model()->lastInsertId();
        }

        if (!empty($data['seo'])) {
            if (!$this->saveSEO($data['seo'], $data[$alias], $alias, 'detail', $error)) {
                $error = [
                    'seo' => $error
                ];

                return false;
            }
        }

        if (!empty($data[$alias]['file_id'])) {
            if (!$this->saveFile([$data[$alias]['file_id']], $data[$alias]['id'], $alias)) {
                return false;
            }
        }
        if (!empty($data[$alias . '-gallery'])) {
            $name = $alias . '-gallery';
            if (!$this->saveFile(array_keys($data[$name]), $data[$alias]['id'], $name, true)) {
                return false;
            }
        }

        if (!empty($data[$alias . '-files'])) {
            $name = $alias . '-files';
            if (!$this->saveFile(array_keys($data[$name]), $data[$alias]['id'], $name, true)) {
                return false;
            }
        }

        $this->saveSearch($data);
        $this->model()->commit();

        return true;
    }

    public function saveSearch($data, $group = [])
    {
        $alias = $this->model()->alias();
        $group = [
            'seo' => [
                'title',
                'keyword',
                'desc'
            ],
            $alias => [
                'category_id',
                'content',
                'manufacturer'
            ]
        ];
        $category = $this->model()->category();

        $search = '';
        foreach ($group as $g => $fields) {
            foreach ($fields as $f) {
                if ($f == 'content') {
                    $search .= strip_tags($data[$g][$f]) . ' ';
                    continue;
                }
                if ($f == 'category_id') {
                    $search .= $category[$data[$alias]['category_id']] . ' ';
                }

                if (isset($data[$g][$f])) {
                    $search .= $data[$g][$f] . ' ';
                }
            }
        }

        $search = Text::slug($search, ' ');
        $search = preg_replace('/[^a-zA-Z0-9 ]+/', '', mb_strtolower($search));

        $data = [
            'keyword' => $search,
            'target_id' => $data[$alias]['id'],
            'target_model' => $alias
        ];

        $model = new \Mp\Model\Search();

        return $model->save($data);
    }
}
