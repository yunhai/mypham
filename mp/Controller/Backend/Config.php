<?php
App::uses('locale', 'controller');

class ConfigBackend extends LocaleBackend {

    public function __construct($table = 'locale', $alias = 'config', $template = 'config') {
        parent::__construct($table, $alias, $template);
    }

    public function index() {
        $request = App::mp('request');

        $page = empty($request->name['page']) ? 1 : $request->name['page'];

        $alias = $this->model()->alias();

        $available = App::mp('config')->get('locale.available');
//hard code: channel = 1
        $option = [
            'select' => "{$alias}.id, {$alias}.code, {$alias}.modified, {$alias}.locale_" . implode(", {$alias}.locale_", array_keys($available)),
            'where' => "channel = 1",
            'order' => "{$alias}.channel asc, {$alias}.scope asc, {$alias}.code asc",
            'page' => $page,
        ];

        $data = $this->paginate($option, true);

        $breadcrumb = ['home' => '', $alias => $alias . '/'];
        $this->render('index', compact('data', 'available', 'breadcrumb'));
    }
}