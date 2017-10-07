<?php

namespace Mp\Core\Controller;

use Mp\App;
use Mp\Core\Master;
use Mp\Lib\Utility\Hash;

class Controller extends Master {

    public function __construct($model = '', $table = '', $alias = '', $template = '') {
        if ($model) {
            $this->model = App::load($model, 'model', compact('table', 'alias'));
        }

        if (empty($template)) {
            $template = App::mp('request')->query['module'];
        }

        $this->templateFolder = $template;
    }

    public function getRoot($alias) {
        return App::category()->root($alias);
    }

    public function getCategory($group, $childOnly = false, $display = 'title', $indent = '', $option = []) {
        return App::category()->flat($group, $childOnly, $display, $indent, $option);
    }

    public function paginate($option = [], $pager = true, &$page = []) {
        return App::load('paginator')->paginate($option, $this->model, $pager, $page);
    }

    public function back($status = null, $refresh = 0) {
        $url = App::mp('request')->referer() ?? '/';
        $this->redirect($url, $status, $refresh);
    }

    public function redirect($location = '/', $status = null, $refresh = 0) {
        $response = App::mp('response');

        $response->statusCode($status);
        $response->header(compact('location'));

        $response->send();
        App::finish();
    }

    public function reload($location = '', $template = '', $option = [], $refresh = 2, $status = null, $folder = '') {
        $response = App::mp('response');

        $response->statusCode($status);
        $response->header(["refresh:{$refresh}; url={$location}"]);

        return $this->render($template, $option, $folder);
    }

    public function render($template = '', $option = [], $folder = '', $addon = true) {
        $this->beforeRender($option, $addon);

        if (empty($folder)) {
            $folder = $this->templateFolder;
        }

        $template = $folder . DS . $template;

        return App::mp('view')->render($template, $option);
    }

    public function beforeRender(&$option = [], $addon = true) {
        if ($addon) {
            $component = App::load('addon/' . App::mp('request')->channel, 'component');
            $option = array_merge($option, $component->init());
        }

        if (empty($option['alias']) && !empty($this->model())) {
            $option['alias'] = $this->model()->alias();
        }

        $this->reference();
    }

    public function reference() {
        $this->variable(['reference' => $this->makeReference()]);
    }

    public function renderJson($data = []) {
        $response = App::mp('response');
        $response->type('json');

        $json = json_encode($data, true);
        App::render($json);
    }

    public function variable($variable = null) {
        return App::mp('view')->variable($variable);
    }

    public function set($key = '', $value = '') {
        return App::mp('view')->variable([$key => $value]);
    }

    public function flash($name = '', $message = null, $group = null) {
        return App::load('flash')->set($name, $message, $group);
    }

    protected $model = null;
    protected $templateFolder = '';

    protected function makeReference() {
        $return = [];
        $ref = App::reference();

        $where = 'target_model = 0';
        if (!empty($ref['seo'])) {
            $where = '(' . $where . ' OR id IN (' . implode(',', $ref['seo']) . '))';
        }

        $option = [
            'select' => 'id, url, alias',
            'where' => 'status > 0 AND ' . $where
        ];

        $tmp = App::mp('seo')->model()->find($option);

        $return['seo'] = Hash::combine($tmp, '{n}.seo.id', '{n}.seo');
        $return['url'] = Hash::combine($tmp, '{n}.seo.url', '{n}.seo.alias');

        if (!empty($ref['file'])) {
            $option = [
                'select' => 'id, directory, name',
                'where' => 'id IN (' . implode(',', $ref['file']) . ')'
            ];

            $tmp = App::mp('file')->model()->find($option);
            $return['file'] = Hash::combine($tmp, '{n}.file.id', '{n}.file');
        }

        return $return;
    }
}