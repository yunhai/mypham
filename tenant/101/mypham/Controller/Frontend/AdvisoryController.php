<?php

use Mp\App;
use Mp\Lib\Utility\Hash;

App::uses('makeup', 'controller');

class AdvisoryController extends MakeupController {

    public function __construct() {
        parent::__construct('advisory', 'post', 'advisory', 'advisory');
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'ask':
                $this->ask();
                break;
            default :
                parent::navigator();
                break;
        }
    }

    public function index() {
        $model = $this->model();
        $alias = $model->alias();
        $seoId = $fileId = [];

        $query = [
            'select' => 'id, title, content, file_id, seo_id',
            'where' => 'status = 1',
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
            'where' => 'id NOT IN (' . $id . ') AND status = 1',
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
            while($i++ < 4) {
                $more[] = array_pop($others);
            }
        }

        $tmp = []; $index = 0;
        $tmp = $this->model()->category();
        foreach ($tmp as $key => $value) {
            if ($index++) {
                $category[$key] = $value;
            }
        }

        $this->render('index', compact('posts', 'more', 'others', 'first', 'category'));
    }

    public function ask() {
        $request = App::mp('request');

        $category = $this->model()->category();

        if (array_key_exists($request->data['category_id'], $category)) {
            $request->data['content'] .= "<hr />Họ tên: {$request->data['name']} / Email: {$request->data['email']}<hr />";
            $request->data['status'] = 2;
            $this->model()->save($request->data);
        }

        $this->back();
    }
}
