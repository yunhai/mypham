<?php

use Mp\App;
use Mp\Lib\Utility\Hash;

App::uses('makeup', 'controller');

class CollectionController extends MakeupController {

    public function __construct() {
        parent::__construct('collection', 'post', 'collection', 'collection');
    }

    public function index() {
        $model = $this->model();
        $alias = $model->alias();
        $seoId = $fileId = [];

        $query = [
            'select' => 'id, title, content, file_id, seo_id',
            'where' => 'status > 0',
            'order' => 'id desc',
            'limit' => 9
        ];

        $posts = App::load('common')->lastest($model, $query);

        if (!empty($posts['main'])) {
            $seoId = [$posts['main']['seo_id']];
            $fileId = [$posts['main']['file_id']];
        }

        if (!empty($posts['list'])) {
            $seoId = array_merge($seoId, Hash::combine($posts['list'], '{n}.seo_id', '{n}.seo_id'));
            $fileId = array_merge($fileId, Hash::combine($posts['list'], '{n}.file_id', '{n}.file_id'));
        }

        $this->refer(['seo' => $seoId, 'file' => $fileId]);

        $lastest = [];
        foreach ($posts as $key => $list) {
            if ($key == 'main') {
                $lastest[] = $list['id'];
                continue;
            }
            $lastest = array_merge($lastest, array_keys($list));
        }

        $id = $lastest ? implode(',', $lastest) : 0;
        $query = [
            'select' => 'id, title, content, file_id, seo_id',
            'where' => 'id NOT IN (' . $id . ') AND status > 0',
            'order' => 'id desc',
            'limit' => 26
        ];

        $more = $first = [];
        $others = $model->find($query);
        if ($others) {
            $others = Hash::combine($others, "{n}.{$alias}.id", "{n}.{$alias}");
            $this->associate($others);

            $first = array_pop($others);
            $i = 1;

            while ($i++ < 4) {
                $more[] = array_pop($others);
            }
        }

        $this->render('index', compact('posts', 'more', 'others', 'first'));
    }

    public function sidebar($current = 0) {
        $model = $this->model();
        $alias = $model->alias();
        $ids = array_keys($model->category());

        $query = [
            'select' => 'id, title, seo_id',
            'where' => 'status > 0',
            'order' => 'idx',
        ];

        $select = "id, title, file_id, seo_id";
        $where = "id <> {$current} AND status > 0";
        $order = 'id desc';
        $limit = 5;
        $random = $model->find(compact('select', 'where', 'order', 'limit'));
        $random = Hash::combine($random, "{n}.{$alias}.id", "{n}.{$alias}");
        $random = $model->associate($random);
        $this->associate($random);

        $this->set('random', $random);
    }
}