<?php

namespace Mp\Core\Controller;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Lib\Utility\Text;
use Mp\Core\Controller\Controller;

class Backend extends Controller
{
    use \Mp\Lib\Traits\Search;

    public function __construct($model, $table = '', $alias = '', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function status($alias = '')
    {
        $status = App::mp('config')->get('status');

        if (empty($status[$alias])) {
            return $status['default'];
        }

        return $status[$alias];
    }

    public function update($status)
    {
        $request = App::mp('request');
        $alias = $this->model()->alias();
        $map = $this->status($alias);

        if (isset($map[$status])) {
            $fields = [
                'status' => $status
            ];

            $condition = 'id IN (' . implode(',', $request->data[$alias]) . ')';
            $this->model()->modify($fields, $condition);
        }

        return $this->back();
    }

    public function getCategory($alias, $childOnly = false, $display = 'title', $spacer = '&nbsp;&nbsp;&nbsp;&nbsp;', $option = [])
    {
        return parent::getCategory($alias, $childOnly, $display, $spacer, $option);
    }

    public function beforeRender(&$option = [], $addon = true)
    {
        parent::beforeRender($option, $addon);

        $alias = $this->model() ? $this->model()->alias() : null;
        $option['status'] = $this->status($alias);
    }

    public function lastCheck(&$data = [])
    {
        $alias = $this->model()->alias();
        if (empty($data[$alias]['file_id'])) {
            $data[$alias]['file_id'] = 0;
        }

        if (isset($data[$alias]['category_id']) && empty($data[$alias]['category_id'])) {
            $data[$alias]['category_id'] = $this->getRoot($alias);
        }
    }

    public function save($data = [], &$error = [], $validator = true)
    {
        $alias = $this->model()->alias();

        $this->lastCheck($data);

        if ($validator) {
            $flag = $this->validate($alias, $data[$alias], $error);

            if (!$flag) {
                $error = [
                    $alias => $error
                ];

                return false;
            }
        }
        $this->model()->begin();
        $this->model()->save($data[$alias]);

        if (empty($data[$alias]['id'])) {
            $data[$alias]['id'] = $this->model()->lastInsertId();
        }

        if (!empty($data['seo'])) {
            if (!$this->saveSEO($data['seo'], $data[$alias], $alias, 'detail', $error)) {
                $error = [
                    'seo' => $error
                ];

                return false;
            }
        }

        if (!empty($data[$alias]['file_id'])) {
            if (!$this->saveFile([$data[$alias]['file_id']], $data[$alias]['id'], $alias)) {
                return false;
            }
        }

        if (!empty($data[$alias . '-gallery'])) {
            $name = $alias . '-gallery';
            if (!$this->saveFile(array_keys($data[$name]), $data[$alias]['id'], $name, true)) {
                return false;
            }
        }

        $this->saveSearch($data);
        $this->model()->commit();

        return true;
    }

    protected function saveSEO($data = [], $target = [], $model = '', $type = 'detail', &$error = [])
    {
        $flag = App::mp('seo')->save($data, $target, $model, $type, $error);

        if ($flag) {
            $option = [
                'fields' => ['seo_id' => $data['id']],
                'where' => 'id = ' . $target['id']
            ];

            return $this->model()->update($option);
        }

        return false;
    }

    public function saveFile($files = [], $targetId = 0, $targetModel = '', $callback = false)
    {
        $model = App::load('file', 'model');
        $update = [
            'target_id' => $targetId,
            'target_model' => $targetModel
        ];

        $id = implode(',', $files);
        $flag = $model->modify($update, 'id IN (' . $id . ')');

        if ($callback) {
            $condition = 'id NOT IN (' . $id . ") AND target_id = {$targetId} AND target_model = '{$targetModel}'";

            return $model->delete($condition);
        }

        return $flag;
    }

    public function saveSearch($data, $group = [])
    {
        $alias = $this->model()->alias();

        if (!$group) {
            $group = [
                'seo' => [
                    'title',
                    'keyword',
                    'description'
                ],
                $alias => [
                    'title',
                    'category_id',
                    'content',
                ]
            ];
        }

        $search = '';
        foreach ($group as $g => $fields) {
            if (empty($data[$g])) {
                continue;
            }

            foreach ($fields as $f) {
                if (empty($data[$g][$f])) {
                    continue;
                }

                if ($f == 'category_id') {
                    $category = $this->model()->category();
                    $search .= $category[$data[$alias]['category_id']] . ' ';
                    continue;
                }

                $search .= strip_tags($data[$g][$f]) . ' ';
            }
        }

        $search = Text::slug($search, ' ');
        $search = preg_replace('/[^a-zA-Z0-9 ]+/', '', mb_strtolower($search));

        $data = [
            'keyword' => $search,
            'target_id' => $data[$alias]['id'],
            'target_model' => $alias
        ];

        $model = new \Mp\Model\Search();

        return $model->save($data);
    }

    public function attach(&$target = [], $alias = '', $fields = ['file' => 'file_id'])
    {
        $files = empty($target[$alias]['file_id']) ? [] : [$target[$alias]['file_id']];

        foreach ($fields as $name => $field) {
            if (empty($target[$alias][$field])) {
                $target[$alias][$field] = [];
                continue;
            }

            $target[$name] = explode(',', $target[$alias][$field]);
            $files = array_merge($files, $target[$name]);
        }

        $this->refer(['file' => $files]);
    }

    public function formatPostData($data = [], $alias = '', $map = [])
    {
        $main = $data[$alias];

        if ($map) {
            foreach ($map as $key => $target) {
                if (isset($data[$key])) {
                    $tmp = Hash::combine($data[$key], '{n}.id', '{n}.id');

                    $tmp = array_filter($tmp);
                    if ($tmp) {
                        $main[$target] = implode(',', $tmp);
                    }

                    unset($data[$key]);
                }
            }
        }

        $result = [
            $alias => $main
        ];
        foreach ($data as $f => $attr) {
            if ($f == $alias) {
                continue;
            }
            $result[$f] = $attr;
        }

        return $result;
    }
}
