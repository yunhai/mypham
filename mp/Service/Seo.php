<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Text;
use Mp\Core\Service\Service;

class Seo extends Service {

    public function __construct() {
        parent::__construct();
        $this->model(new \Mp\Model\Seo());
    }

    public function search($url = '', &$target = []) {

        $alias = $this->model()->alias();

        $select = "{$alias}.url, {$alias}.canonical, {$alias}.title, {$alias}.keyword, {$alias}.description";
        $where = "{$alias}.alias='" . $url . "'";
        $model = new \Mp\Model\Seo();
        $target = $model->find(compact('select', 'where'), 'first');

        if ($target) {
            $target = $target[$alias];
            return $target['url'];
        }
        return '';
    }

    public function target($id = 0, $init = true) {
        if ($id) {
            $alias = $this->model()->alias();
            $fields = "{$alias}.id, {$alias}.alias, {$alias}.canonical, {$alias}.title, {$alias}.keyword, {$alias}.description";

            $result = $this->model()->findById($id, $fields);
            if ($result || !$init) {
                return $result;
            }
        }
        return $this->model()->init();
    }

    public function save(&$data = [], $target = [], $module = '', $type = 'detail', &$error = []) {
        $data = $this->generate($data, $target, $module, $type);


        $flag = $this->validate('seo', $data, $error);
        if ($flag) {
            $model = $this->model();

            if ($model->save($data)) {
                if (empty($data['id'])) {
                    $data['id'] = $model->lastInsertId();
                }

                return true;
            }
        }

        return false;
    }

    public function generate($data = [], $target = [], $module = '', $type = 'detail') {
        if (empty($data['alias'])) {
            $data['alias'] = $this->aliasUrl($module, $type, $target);
        }

        if (empty($data['canonical'])) {
            $data['canonical'] = $data['alias'];
        }

        if (empty($data['title'])) {
            $data['title'] = $target['title'];
        }

        if (empty($data['keyword'])) {
            $keyword = $target['title'];
            $data['keyword'] = str_replace(' ', ', ', mb_strtolower($keyword));
        }

        if (empty($data['description'])) {
            $option = ['html' => false];
            $run = array('content', 'title');

            $content = '';
            foreach ($run as $key) {
                if (empty($target[$key])) {
                    continue;
                }

                $content = strip_tags($target[$key]);
            }

            $data['desc'] = Text::truncate($content, 150, $option);
        }

        $data['app_id'] = App::mp('login')->appId();
        $data['url'] = $this->realUrl($module, $type, $target);

        $data['target_id'] = $target['id'];
        $data['target_model'] = $module;

        return $data;
    }

    public function realUrl($module = '', $type = '', $target = []) {
        if ($type == 'index') {
            return $module;
        }
        return $module . '/' . $type . '/' . $target['id'];
    }

    public function aliasUrl($module = '', $type = '', $target = []) {
        $prefix = App::mp('config')->get("seo.prefix.{$module}.{$type}");

        if (is_null($prefix)) {
            $prefix = '';
            if ($type != 'index') {
                $prefix = $module . '/' . $type . '/';
            }
        } elseif ($prefix) {
            $prefix .= '/';
        }
        return $prefix . Text::slug($target['title']);
    }
}