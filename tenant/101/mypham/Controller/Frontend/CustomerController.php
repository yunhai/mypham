<?php

use Mp\App;
use Mp\Lib\Utility\Hash;

App::uses('makeup', 'controller');

class CustomerController extends MakeupController {

    public function __construct() {
        parent::__construct('customer', 'post', 'customer', 'customer');
    }

    public function index() {
        $model = $this->model();
        $alias = $model->alias();
        $lastest = $seoId = $fileId = [];

        $query = [
            'select' => 'id, title, content, file_id, seo_id',
            'where' => 'status = 1',
            'order' => 'id desc',
            'limit' => 9
        ];

        $posts = App::load('common')->lastest($model, $query);

        if (isset($posts['main'])) {
            $seoId = [$posts['main']['seo_id']];
            $fileId = [$posts['main']['file_id']];
        }

        if (isset($posts['list'])) {
            $seoId = array_merge($seoId, Hash::combine($posts['list'], '{n}.seo_id', '{n}.seo_id'));
            $fileId = array_merge($fileId, Hash::combine($posts['list'], '{n}.file_id', '{n}.file_id'));
        }

        $this->refer(['seo' => $seoId, 'file' => $fileId]);

        foreach ($posts as $key => $list) {
            if ($key == 'main') {
                $lastest[] = $list['id'];
                continue;
            }
            $lastest = array_merge($lastest, array_keys($list));
        }

        $others = $first = $more = [];
        if ($lastest) {
            $query = [
                'select' => 'id, title, content, file_id, seo_id',
                'where' => 'id NOT IN (' . implode(',', $lastest) . ') AND status = 1',
                'order' => 'id desc',
                'limit' => 26
            ];

            $others = $model->find($query);
            $others = Hash::combine($others, "{n}.{$alias}.id", "{n}.{$alias}");
            $this->associate($others);

            $first = array_pop($others);
            $i = 1;

            while($i++ < 4) {
                $more[] = array_pop($others);
            }
        }

        $category = App::category()->flat('advisory', true);
        $this->render('index', compact('posts', 'more', 'others', 'first', 'category'));
    }
}
