<?php

namespace Mp\Core;

use Mp\App;
use Mp\Lib\Utility\Hash;

class Master
{
    public function model($model = null)
    {
        if (is_null($model)) {
            return $this->model;
        }

        return $this->model = $model;
    }

    public function log($info = '', $type = 'error')
    {
        App::log($info, $type);
    }

    // 1 : single; 2: multiple - first; 3: multiple - all
    public function validate($alias = '', $data = [], &$error = [], $level = 1, $rule = 'def')
    {
        $validator = $this->validator($alias)->rule($rule);

        if ($level == 1) {
            return $validator->validate($data, $error);
        }

        $e = [];
        foreach ($data as $index => $target) {
            $flag = $validator->validate($target, $e);
            if ($flag) {
                continue;
            }

            $error[$index] = $e;
            if ($level == 2) {
                return false;
            }
        }

        return true;
    }

    public function validator($alias)
    {
        return App::load($alias, 'validator');
    }

    public function refer($target = [])
    {
        App::refer($target);
    }

    public function associate($list = [], $fields = ['seo' => 'seo_id', 'file' => 'file_id'])
    {
        if ($list && $fields) {
            $refer = [];
            foreach ($fields as $key => $field) {
                $refer[$key] = Hash::combine($list, '{n}.' . $field, '{n}.' . $field);
            }

            $this->refer($refer);
        }
    }

    public function master($data = [], $model = '')
    {
        $result = [];
        foreach ($data as $key => $item) {
            if ($key == $model) {
                $result = array_merge($result, $item);
                continue;
            }
            $result[$key] = $item;
        }

        return $result;
    }
}
