<?php

namespace Mp\Lib\Traits;

use Mp\App;
use Mp\Lib\Utility\Hash;

trait Extension
{
    private $lastInsertId = null;
    private $extension = null;
    private $virtualField = [
        'string_1' => 'string_1',
        'string_2' => 'string_2',
        'string_3' => 'string_3',
        'string_4' => 'string_4',
        'string_5' => 'string_5',
        'text_1' => 'text_1',
    ];

    public function extend($fields = [])
    {
        $this->extension = new \Mp\Model\Extension();
        if ($fields) {
            $this->virtualField = $fields;
        }
    }

    public function lastInsertId()
    {
        return $this->lastInsertId;
    }

    public function removeExtension()
    {
        $this->extension = null;
        $this->virtualField = [];
    }

    public function virtualField($virtualField = null)
    {
        if (is_null($virtualField)) {
            return $this->virtualField;
        }

        $this->virtualField = $virtualField;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function loadExtension($extension = null)
    {
        return $this->extension = $extension;
    }

    public function extensionData($id = '', $option = [])
    {
        if (is_null($this->extension)) {
            return [];
        }

        $fields = implode(', extension.', array_keys($this->virtualField));

        $default = [
            'select' => 'extension.id, extension.target_id, extension.' . $fields,
            'where' => "extension.target_model = '{$this->alias()}' AND extension.target_id IN ({$id})",
            'order' => 'extension.target_id'
        ];

        $default = array_merge($default, $option);

        return $this->extension->find($default);
    }

    public function raw($option = [], $type = 'all')
    {
        return $this->extension->find($option, $type);
    }

    public function getRaw($option = [], $type = 'all')
    {
        $data = $this->raw($option, $type);
        $data = Hash::combine($data, '{n}.extension.id', '{n}.extension');
        $result = [];
        foreach ($data as $id => $item) {
            $result[$id] = ['id' => $item['id']];
            $this->mapExtension($result[$id], $data);
        }

        return $result;
    }

    public function extractExtension(&$data, $option = [])
    {
        if (empty($data)) {
            return [];
        }

        if (empty($this->virtualField)) {
            return $data;
        }

        $single = Hash::dimensions($data) < 3;

        $category = $id = [];

        if ($single) {
            $item = current($data);
            if (empty($item['id'])) {
                return $data;
            }
            $id[$item['id']] = $item['id'];
        } else {
            foreach ($data as $item) {
                $item = current($item);
                if (empty($item['id'])) {
                    continue;
                }
                $id[$item['id']] = $item['id'];
            }
        }

        if ($id) {
            $id = implode(',', $id);
            $extension = $this->extensionData($id, $option);

            if ($extension) {
                $extension = Hash::combine($extension, '{n}.extension.target_id', '{n}.extension');
            }

            if ($single) {
                $this->mapExtension($data[$this->alias()], $extension);
            } else {
                foreach ($data as &$item) {
                    $this->mapExtension($item[$this->alias()], $extension);
                }
            }
        }


        return $data;
    }

    public function mapExtension(&$target = [], $extension = [])
    {
        foreach ($this->virtualField as $f => $virtual) {
            $id = $target['id'];

            $result = isset($extension[$id]) ? $extension[$id][$f] : '';
            if (is_array($virtual)) {
                if ($result) {
                    $result = json_decode($result, true);
                } else {
                    foreach ($virtual as $vf) {
                        $result[$vf] = '';
                    }
                }
                $target = array_merge($target, $result);
            } else {
                $target[$virtual] = $result;
            }

            unset($target[$f]);
        }

        return $target;
    }

    public function init($fields = [])
    {
        $init = parent::init();
        $this->mapExtension($init[$this->alias()], []);

        return $init;
    }

    public function save($data)
    {
        $this->beforeSave($data);

        if (empty($data)) {
            return true;
        }

        $flag = $this->makeSave($data);

        if ($flag == false) {
            return false;
        }

        if (empty($data['id'])) {
            $this->lastInsertId = $data['id'] = parent::lastInsertId();
        }


        return $this->saveExtension($data);
    }

    public function delete($condition = '', $association = [])
    {
        $alias = $this->alias();
        $option = [
            'select' => "{$alias}.id, {$alias}.id",
            'where' => $condition,
        ];
        $target = $this->find($option, 'list');

        $flag = parent::delete($condition, $association);
        if ($flag == false) {
            return false;
        }

        if (is_null($this->extension)) {
            return true;
        }

        return $this->extension->deleteByList($target, $alias);
    }

    public function saveExtension($data = [])
    {
        if (is_null($this->extension)) {
            return true;
        }

        return $this->extension->saveByTarget($this->revertExtension($data));
    }

    public function modifyPk($data = [], $condition = '')
    {
        return parent::modify($data, $condition);
    }

    public function modify($data = [], $condition = '')
    {
        if (is_null($this->extension)) {
            return parent::modify($data, $condition);
        }

        $flag = parent::modify($data, $condition);

        if (!$flag) {
            return false;
        }

        return $this->extension->saveByTarget($this->revertExtension($data));
    }

    public function saveRaw($data = [])
    {
        return $this->extension->save($this->parseExtension($data));
    }

    public function parseExtension($data = [], $default = true)
    {
        foreach ($this->virtualField as $field => $virtualField) {
            if (is_array($virtualField)) {
                $array = [];
                foreach ($virtualField as $vf) {
                    if ($default) {
                        $array[$vf] = '';
                    }
                    if (isset($data[$vf])) {
                        $array[$vf] = $data[$vf];
                        unset($data[$vf]);
                    }
                }

                if ($array) {
                    $data[$field] = json_encode($array, JSON_UNESCAPED_UNICODE);
                }
            } else {
                if (isset($data[$virtualField])) {
                    $data[$field] = $data[$virtualField];
                    unset($data[$virtualField]);
                }
            }
        }

        return $data;
    }

    public function revertExtension($data = [], $default = true)
    {
        $info = [
            'target_id' => $data['id'],
            'target_model' => $this->alias(),
        ];

        unset($data['id']);

        return array_merge($info, $this->parseExtension($data, $default));
    }

    public function afterFind(&$data = [])
    {
        parent::afterFind($data);
        
        if ($this->extension) {
            return $this->extractExtension($data);
        }

        return true;
    }
}
