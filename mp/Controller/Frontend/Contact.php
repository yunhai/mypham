<?php

namespace Mp\Controller\Frontend;

use Mp\App;
use Mp\Core\Controller\Frontend;

class Contact extends Frontend
{
    public function __construct($model = 'contact', $table = 'contact', $alias = 'contact', $template = 'contact')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'finish':
                $this->finish();
                break;
            default:
                $this->index();
                break;
        }
    }

    public function index()
    {
        $request = App::mp('request');

        $alias = $this->model()->alias();
        $data = [];

        if (!empty($request->data)) {
            $json = [];
            $exclude = ['id', 'content'];
            foreach ($request->data as $key => $value) {
                if (in_array($key, $exclude)) {
                    $data[$key] = $value;
                    continue;
                }
                $json[$key] = $value;
            }

            $data['code'] = mb_strtoupper(dechex(time()));
            $data['info'] = json_encode($json, true);
            $flag = $this->model()->save($data);
            if ($flag) {
                $this->email($request->data);
                $this->redirect(App::load('url')->module('finish'));
            }
            $this->set('target', $request->data);
        }

        $this->render('form');
    }

    public function finish()
    {
        $this->render('finish');
    }

    protected function email($data = [])
    {
        $common = App::load('common');

        $info = [
            'to' => $data['email']
        ];

        $common->sendEmail('32002', $data, $info); // to client
        $common->sendEmail('32001', $data); // to admin
    }
}
