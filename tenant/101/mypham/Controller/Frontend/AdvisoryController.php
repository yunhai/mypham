<?php

use Mp\App;
use Mp\Lib\Utility\Hash;

App::uses('makeup', 'controller');

class AdvisoryController extends MakeupController
{
    public function __construct()
    {
        parent::__construct('advisory', 'post', 'advisory', 'advisory');
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'ask':
                $this->ask();
                break;
            default:
                parent::navigator();
                break;
        }
    }

    public function sidebar()
    {
        $sidebar_advisory = true;
        $this->variable(compact('sidebar_advisory'));

        return parent::sidebar();
    }

    public function detail($id = 0)
    {
        $alias = $this->model()->alias();

        $select = "{$alias}.id, {$alias}.title, {$alias}.content, {$alias}.category_id";
        $where = "{$alias}.id = {$id} AND {$alias}.status = 1";

        $target = $this->model()->find(compact('select', 'where'), 'first');
        if (empty($target)) {
            abort('NotFoundException');
        }

        $target = $target[$alias];
        $others = $this->other($target);
        $sidebar = $this->sidebar();

        $breadcrumb = [
            $sidebar['category'][$target['category_id']],
            $target
        ];

        $this->set('breadcrumb', $breadcrumb);

        $this->render('detail', compact('target', 'others'));
    }

    public function ask()
    {
        $request = App::mp('request');

        if (!empty($request->data)) {
            $category = $this->model()->category();

            $data = $request->data;
            if (array_key_exists($data['category_id'], $category)) {
                $data['status'] = 2;
                $this->model()->save($data);
            }

            $info = [
                'to' => $data['email']
            ];
            $this->email('m4001', $data); // to admin
            $this->email('m4002', $data, $info); // to client
        }

        $sidebar = $this->sidebar();
        $breadcrumb = [
            ['title' => 'Gửi tư vấn']
        ];

        $this->set('breadcrumb', $breadcrumb);

        $this->render('finish');
    }

    protected function email($template = '', $data = [], $mail = [], $priority = 30)
    {
        $common = App::load('common');
        $common->sendEmail($template, $data, $mail);
    }
}
