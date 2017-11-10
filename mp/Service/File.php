<?php

namespace Mp\Service;

use Mp\App;
use Mp\Core\Service\Service;

use Mp\Lib\Utility\Hash;
use Mp\Lib\Utility\Text;

class File extends Service {
    public function __construct($model = 'file', $table = 'file', $alias = 'file') {
        parent::__construct($model, $table, $alias);
    }

    public function upload($single = true, $target = '') {
        $result = [];

        $media = App::load('html')->media();
        $data = $this->init(App::load('file'), App::mp('config'), $single, $target);

        foreach ($data as $item) {
            $this->model()->save($item);

            $item['id'] = $this->model()->lastInsertId();

            $url = $media . '/' . $item['directory'] . $item['name'];
            $item['target'] = $url;

            $result[$item['container']][] = $item;
        }

        return $result;
    }

    private function init($fileHelper, $config, $single = true, $target = '') {
        $data = [];

        $location = $config->get('app.upload.location');
        $directories = $config->get('module');

        $destination = ROOT . 'public' . DS . $location . DS . App::mp('login')->targetCode() . DS;

        if ($single) {
            foreach ($_FILES as $container => $item) {
                if ($_FILES[$container]['tmp_name']) {
                    $directory = $container;
                    if (!empty($folder[$container])) {
                        $directory = $folder[$container];
                    }

                    $info = $_FILES[$container];

                    $target = $target ? $target : $container;
                    $directory = empty($directories[$target]) ? $target : $directories[$target];

                    $data[] = $this->makeUpload($fileHelper, $info, $container, $destination, $directory);
                }
            }
            return $data;
        }

        foreach ($_FILES as $key => $item) {
            foreach ($_FILES[$key]['tmp_name'] as $index => $ignore) {
                $info = [
                    'name' => $_FILES[$key]['name'][$index],
                    'type' => $_FILES[$key]['type'][$index],
                    'error' => $_FILES[$key]['error'][$index],
                    'tmp_name' => $_FILES[$key]['tmp_name'][$index],
                    'size' => $_FILES[$key]['size'][$index],
                ];

                $directory = empty($directories[$container]) ? $container : $directories[$container];
                $data[] = $this->makeUpload($fileHelper, $info, $container, $destination, $directory);
            }
        }

        return $data;
    }

    private function makeUpload($fileHelper, $info = [], $container = '', $destination = '', $directory = '') {
        $tmp = pathinfo($info['name']);
        $ext = $tmp['extension'];

        $info['extension'] = $ext;
        $info['filename'] = Text::slug($tmp['filename'], '-') . '-' . date('YmdHis') . '.' . $ext;

        $error = null;
        $destination .= $directory;
        $flag = $fileHelper->upload($info, $destination, $error);

        return [
            'success' => (bool)$flag,
            'error' => $error,
            'extension' => $info['extension'],
            'container' => $container,
            'real_name' => $info['name'],
            'directory' => $directory . '/',
            'name' => $info['filename'],
            'size' => $info['size'],
        ];
    }

    public function target($id = [], $option = []) {
        if (empty($id)) {
            return [];
        }

        if (is_array($id)) {
            $id = implode(',', $id);
        }

        $alias = $this->model()->alias();
        $default = [
            'select' => "{$alias}.id, {$alias}.name, {$alias}.directory",
            'where' => "id IN (" . $id . ')',
            'order' => "{$alias}.id desc",
        ];

        $default = array_merge($default, $option);

        $result = $this->model()->find($default);
        return Hash::combine($result, '{n}.file.id', '{n}.file');
    }
}