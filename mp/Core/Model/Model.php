<?php

namespace Mp\Core\Model;

use Mp\App;
use Mp\Lib\Utility\Hash;

class Model
{
    public function associate($target = [], $association = [])
    {
        $retrieve = $associationResult = [];
        foreach ($association as $key => $field) {
            $tmp = [];
            foreach ($target as $id => $item) {
                $ref = $item[$key . '_id'];
                $tmp[$id] = $ref;
            }
            $retrieve[$key] = array_filter($tmp);
        }

        foreach ($retrieve as $key => $id) {
            if ($id) {
                $model = '\Mp\Model\\' . ucfirst($key);
                $model = new $model();

                $option = [
                    'select' => $association[$key],
                    'where' => 'id IN  (' . implode(',', $id) . ')'
                ];

                $associationResult[$key] = $model->find($option);
            }
        }

        foreach ($retrieve as $alias => $list) {
            foreach ($list as $origin => $ref) {
                $tmp = empty($associationResult[$alias][$ref]) ? [] : $associationResult[$alias][$ref][$alias];
                $target[$origin][$alias] = $tmp;
            }
        }

        return $target;
    }

    //0: delete, 1: create, 2: modify
    public function tracklog($action = 2)
    {
        $userId = App::mp('login')->userId();
        $userId = $userId ? $userId : 0;

        switch ($action) {
            case 0:
                return [
                    'deleted' => 'NOW()',
                    'delete_by' => $userId,
                ];
                break;
            case 1:
                return [
                    'modified' => 'NOW()',
                    'editor' => $userId,
                    'created' => 'NOW()',
                    'creator' => $userId,
                ];
            default:
                return [
                    'modified' => 'NOW()',
                    'editor' => $userId,
                ];
                break;
        }
    }

    public function baseCondition()
    {
        return $this->alias . '.deleted is null';
    }

    public function baseConditionWithAppId()
    {
        return "{$this->alias}.app_id = " . App::mp('login')->targetId() . " AND {$this->alias}.deleted is null";
    }

    public function beforeFind(&$data = [])
    {
        return true;
    }

    public function afterFind(&$data = [])
    {
        return true;
    }

    public function beforeDelete(&$condition = '', $association = [])
    {
        if ($association) {
            $option = [
                'select' => 'id,' . implode(',', $association),
                'where' => $condition
            ];

            $master = $this->find($option, 'all', 'id', false);

            if ($master) {
                $master = Hash::combine($master, "{n}.{$this->alias}.id", "{n}.{$this->alias}");
                $target = [];
                foreach ($association as $key) {
                    $aModel = ucfirst(str_replace('_id', '', $key));
                    $target[$aModel] = [];
                    foreach ($master as $item) {
                        $id = $item[$key];
                        $target[$aModel][$id] = $id;
                    }
                    array_filter($target[$aModel]);
                }

                foreach ($target as $aModelStr => $id) {
                    if ($id) {
                        $id = implode(',', $id);
                        $aModelName = '\Mp\Model\\' . $aModelStr;
                        $aModel = new $aModelName();

                        $aCondition = "id IN ({$id})";
                        $flag = $aModel->delete($aCondition);
                        if ($flag == false) {
                            $this->_error = [
                                printf('delete association [%s] fail.', $aModelStr)
                            ];

                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function beforeSave(&$data = [])
    {
        $upsert = empty($data[$this->pk]) ? 1 : 2;
        $data = array_merge($data, $this->tracklog($upsert));

        return true;
    }

    public function beforeModify(&$data = [])
    {
        $default = $this->tracklog();
        if (Hash::dimensions($data) > 1) {
            foreach ($data as $key => $item) {
                $data[$key] = array_merge($data[$key], $default);
            }
        } else {
            $data = array_merge($data, $default);
        }

        return true;
    }

    public function findById($id, $fields = '', $callback = true, $option = [])
    {
        $default = [
            'select' => empty($fields) ? '*' : $fields,
            'where' => "{$this->alias}.id = {$id}",
            'order' => "{$this->alias}.id desc",
            'limit' => 1,
        ];
        $default = array_merge($default, $option);

        if ($callback) {
            $result = [];
            if ($this->beforeFind($default)) {
                $result = $this->findFirst($default);
                $this->afterFind($result);
            }

            return $result;
        }

        return $this->findFirst($default);
    }

    public function find($option = [], $type = 'all', $key = 'id', $callback = true)
    {
        $result = [];

        $func = 'find' . ucfirst($type);
        if ($callback) {
            if ($this->beforeFind($option)) {
                $result = $this->$func($option, $key);
                $callback = ['all', 'first'];
                if (in_array($type, $callback)) {
                    $this->afterFind($result);
                }
            }

            return $result;
        }

        return $this->$func($option, $key);
    }

    private function findAll($option, $key = 'id')
    {
        $result = $this->retrieve($option);

        if (empty($result)) {
            return [];
        }

        if (Hash::check($result, "{n}.{$this->alias}.{$key}")) {
            return Hash::combine($result, "{n}.{$this->alias}.{$key}", '{n}');
        }

        return $result;
    }

    private function findFirst($option)
    {
        $option['limit'] = 1;
        $result = $this->retrieve($option);

        if (empty($result)) {
            return [];
        }

        return current($result);
    }

    private function findCount($option)
    {
        unset($option['limit'], $option['page'], $option['order']);

        $option['select'] = 'count(' . $this->alias . '.id) as count';

        $tmp = $this->retrieve($option);

        return empty($tmp) ? 0 : $tmp[0][0]['count'];
    }

    private function findList($option)
    {
        $result = $this->retrieve($option);

        if (empty($result)) {
            return [];
        }

        $exp = explode(',', strtr($option['select'], [' ' => '']));

        $key = strtr($exp[0], [$this->alias . '.' => '']);
        if (empty($exp[1])) {
            $value = $key;
        } else {
            $value = strtr($exp[1], [$this->alias . '.' => '']);
        }

        return Hash::combine($result, "{n}.{$this->alias}.{$key}", "{n}.{$this->alias}.{$value}");
    }

    public function delete($condition = '', $association = [])
    {
        $flag = $this->beforeDelete($condition, $association);

        if ($flag == false) {
            return false;
        }

        $userId = App::mp('login')->userId();

        if ($this->baseCondition()) {
            $condition .= ' AND ' . $this->baseCondition();
        }

        $option = [
            'fields' => $this->tracklog(0),
            'where' => $condition
        ];

        return $this->update($option);
    }

    public function save($data)
    {
        $this->beforeSave($data);

        if (empty($data)) {
            return true;
        }

        return $this->makeSave($data);
    }

    public function saveMany($data)
    {
        if (empty($data)) {
            return true;
        }

        $result = $insert = $update = [];

        $baseCondition = $this->baseCondition() ? ' AND ' . $this->baseCondition() : '';

        foreach ($data as $key => $item) {
            if (empty($item[$this->pk])) {
                $insert[] = $item;
                continue;
            }

            $id = $item[$this->pk];
            $option = [
                'fields' => $item,
                'where' => $this->pk . ' = ' . $id . $baseCondition
            ];

            $result['update'][$id] = $this->update($option);
        }

        if (empty($insert) == false) {
            $result['insert'] = $this->create($insert, false);
        }

        if (empty($result['update'])) {
            return $result['insert'];
        }

        if (empty($result['insert'])) {
            return !(in_array(false, $result['update']));
        }

        return $result['insert'] && !(in_array(false, $result['update']));
    }

    public function init($fields = [])
    {
        $value = ['status' => 1];
        $default = [];

        if (empty($fields)) {
            $fields = $this->getColumn();
        }

        foreach ($fields as $f) {
            $default[$f] = empty($value[$f]) ? '' : $value[$f];
        }

        return [$this->alias => $default];
    }

    public function modify($data = [], $condition = '')
    {
        if ($this->beforeModify($data)) {
            if ($this->baseCondition()) {
                $condition .= ' AND ' . $this->baseCondition();
            }

            $option = [
                'fields' => $data,
                'where' => $condition
            ];

            return $this->update($option) !== false;
        }

        return false;
    }

    public function forceModify($data = [], $condition = '')
    {
        $option = [
            'fields' => $data,
            'where' => $condition
        ];

        return $this->update($option) !== false;
    }

    ///////////////////////////////////////////////////////////////////////////
    protected function makeSave($data)
    {
        if (empty($data[$this->pk])) {
            return $this->create([$data]);
        }

        $baseCondition = $this->baseCondition();
        $baseCondition = $baseCondition ? ' AND ' . $baseCondition : '';
        $option = [
            'fields' => $data,
            'where' => $this->pk . ' = ' . $data[$this->pk] . $baseCondition
        ];

        return $this->update($option);
    }

    protected function retrieve($option = [])
    {
        if (empty($option['where'])) {
            $option['where'] = '1';
        }

        $option['where'] .= $this->baseCondition() ? ' AND ' . $this->baseCondition() : '';
        $option['from'] = [$this->table => $this->alias];

        $query = App::db()->buildQuery($option);

        $q = App::db()->renderStatement('select', $query);

        return App::db()->query($q);
    }

    public function getQueries($full = false)
    {
        $log = App::db()->getLog();
        if ($full) {
            return $log;
        }

        $tmp = [];
        foreach ($log['log'] as $item) {
            $tmp[] = $item['query'];
        }

        return $tmp;
    }

    public function query($query = '')
    {
        return App::db()->query($query);
    }

    public function create($data = [])
    {
        foreach ($data as $key => &$item) {
            unset($item[$this->pk]);
        }

        $option = [
            'from' => $this->table,
            'fields' => $data
        ];

        $query = App::db()->buildQuery($option, 'create');
        $q = App::db()->renderStatement('create', $query);

        return App::db()->query($q, true);
    }

    public function update($data)
    {
        $data['from'] = [$this->table => $this->alias];

        $query = App::db()->buildQuery($data, 'update');

        $q = App::db()->renderStatement('update', $query);

        return App::db()->query($q, true);
    }

    public function lastInsertId()
    {
        return App::db()->lastInsertId();
    }

    public function getColumn()
    {
        return App::db()->getColumn($this->table);
    }

    public function begin()
    {
        return App::db()->begin();
    }

    public function commit()
    {
        return App::db()->commit();
    }

    public function rollback()
    {
        return App::db()->rollback();
    }

    public function nestedTransaction($stutus = null)
    {
        return App::db()->nestedTransaction($stutus);
    }

    protected $table = '';

    protected $alias = '';

    protected $pk = '';

    public function __construct($table = '', $alias = '', $primaryKey = 'id')
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->pk = $primaryKey;
    }

    public function alias()
    {
        return $this->alias;
    }

    public function table()
    {
        return $this->table;
    }

    public function pk()
    {
        return $this->pk;
    }
}
