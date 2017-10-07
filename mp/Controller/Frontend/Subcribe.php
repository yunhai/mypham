<?php

namespace Mp\Controller\Frontend;

use Mp\App;
use Mp\Core\Controller\Frontend;

class Subcribe extends Frontend {

    public function __construct($model = '', $table = '', $alias = 'subcribe', $template = 'subcribe') {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'finish':
                $this->finish();
                break;
            case 'cancel':
                $this->cancel();
                break;
            default :
                $this->add();
                break;
        }
    }

    public function cancel() {
        $request = App::mp('request');

        if (!empty($request->data)) {
            $model = App::load('mailRecipient', 'model');
            $model->delete("email = '{$request->data['email']}'");
            return $this->render('cancel_finish');
        }

        $this->render('cancel');
    }

    public function add() {
        $request = App::mp('request');

        if (!empty($request->data)) {
            $model = App::load('mailRecipient', 'model');
            $model->save($request->data);
            $this->redirect(App::load('url')->module('finish'));
        }

        $this->redirect(App::load('url')->full());
    }

    public function finish() {
        $this->render('finish');
    }
}