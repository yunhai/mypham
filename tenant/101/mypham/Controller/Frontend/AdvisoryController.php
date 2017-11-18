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

        $category = $this->model()->category();

        if (array_key_exists($request->data['category_id'], $category)) {
            $request->data['status'] = 2;
            $this->model()->save($request->data);
        }

        // send mail

        $this->back();
    }
}
