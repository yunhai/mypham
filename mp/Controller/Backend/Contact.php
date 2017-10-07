<?php

namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Core\Controller\Backend;

class Contact extends Order
{
    public function __construct($model = 'contact', $table = 'contact', $alias = 'contact', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'detail':
                $this->detail($request->query[2]);
                break;
            case 'delete':
                $this->delete();
                break;
            case 'finish':
                $this->handle($request->query[2], 2);
                break;
            case 'reject':
                $this->handle($request->query[2], 3);
                break;
            case 'rollback':
                $this->handle($request->query[2]);
                break;
            case 'reply':
                $this->reply();
                break;
            case 'feedback':
                $this->handle($request->query[2]);
                break;
            case 'update':
                $this->update($request->query[2]);
                break;
            default:
                $this->index();
                break;
        }
    }

    public function reply()
    {
        $request = App::mp('request');
        if (!empty($request->data['email'])) {
            $common = App::load('common');
            $info = $request->data['email'];

            $common->sendEmail('991001', [], $info);
            $id = $request->data['email']['target_id'];
        }

        $this->handle($id, 1);
    }

    public function index()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $list = $data = [];

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $option = [
            'select' => "{$alias}.id, {$alias}.code, {$alias}.status, {$alias}.content, {$alias}.modified, {$alias}.created",
            'order' => "{$alias}.id desc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);

        $this->render('index', compact('data', 'option'));
    }

    public function detail($id = 0)
    {
        $id = (int) $id;

        $alias = $this->model()->alias();

        $fields = "{$alias}.id, {$alias}.code, {$alias}.status, {$alias}.content, {$alias}.info, {$alias}.modified, {$alias}.created";

        $target = $this->model()->findById($id, $fields);
        if (empty($target)) {
            abort('NotFoundException');
        }

        if ($target[$alias]['info']) {
            $target[$alias]['info'] = json_decode($target[$alias]['info']);
        }

        return $this->render('detail', compact('target', 'option'));
    }
}
