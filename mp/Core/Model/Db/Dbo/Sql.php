<?php
namespace Mp\Core\Model\Db\Dbo;

use Mp\App;
use \PDO as PDO;

class Sql {

    /**
     * String to hold how many rows were affected by the last SQL operation.
     *
     * @var string
     */
    public $affected = null;

    /**
     * Number of rows in current resultset
     *
     * @var integer
     */
    public $numRows = null;

    /**
     * Time the last query took
     *
     * @var integer
     */
    public $took = null;

    /**
     * Result
     *
     * @var array
     */
    protected $_result = null;

    /**
     * Queries count.
     *
     * @var integer
     */
    protected $_queriesCnt = 0;

    /**
     * Total duration of all queries.
     *
     * @var integer
     */
    protected $_queriesTime = null;

    /**
     * Log of queries executed by this DataSource
     *
     * @var array
     */
    protected $_queriesLog = [];

    public function __construct($config = []) {
    }

    public function renderStatement($type, $data) {
        //extract($data);
        $aliases = null;

        switch (strtolower($type)) {
            case 'select':
                return $this->select($data);
            case 'create':
                return $this->create($data);
            case 'update':
                return $this->update($data);
           case 'delete':
                if (!empty($alias)) {
                   $aliases = "{$this->alias}{$alias} {$join} ";
                }
                return trim("DELETE {$alias} FROM {$table} {$aliases}{$conditions}");
        }
    }

    public function hasResult() {
        if (is_object($this->_result)) {
            return get_class($this->_result) == 'PDOStatement';
        }

        return false;
    }

    public function fetchAll($query, $params = [], $options = []) {
        $result = $this->execute($query, [], $params);

        if ($result) {
            $out = [];

            if ($this->hasResult()) {
                $first = $this->fetchRow();
                if ($first) {
                    $out[] = $first;
                }
                while ($item = $this->fetchResult()) {
                    $out[] = $item;
                }
            }

            if (empty($out) && is_bool($this->_result)) {
                return $this->_result;
            }

            return $out;
        }

        return false;
    }

    public function query($query, $nonQuery = false) {
        if ($nonQuery) {
            $return = $this->execute($query);
            return !empty($return);
        }

        return $this->fetchAll($query);
    }

    public function execute($sql, $options = [], $params = []) {
        $t = microtime(true);
        $this->_result = $this->_execute($sql, $params);

        $this->took = round((microtime(true) - $t) * 1000, 0);
        $this->numRows = $this->affected = $this->lastAffected();
        $this->logQuery($sql, $params);

        return $this->_result;
    }

    /**
     * Returns number of affected rows in previous database operation. If no previous operation exists,
     * this returns false.
     *
     * @param mixed $source
     * @return integer Number of affected rows
     */
    public function lastAffected($source = null) {
        if ($this->hasResult()) {
            return $this->_result->rowCount();
        }

        return 0;
    }

    /**
     * Log given SQL query.
     *
     * @param string $sql SQL statement
     * @param array $params Values binded to the query (prepared statements)
     * @return void
     */
    public function logQuery($sql, $params = []) {
        $this->_queriesCnt++;
        $this->_queriesTime += $this->took;
        $this->_queriesLog[] = [
            'query' => $sql,
            'params' => $params,
            'affected' => $this->affected,
            'numRows' => $this->numRows,
            'took' => $this->took
        ];
    }

    public function getLog($sorted = false, $clear = true) {
        $log = $this->_queriesLog;
        if ($clear) {
            $this->_queriesLog = [];
        }
        return ['log' => $log, 'count' => $this->_queriesCnt, 'time' => $this->_queriesTime];
    }

    protected function _execute($sql, $params = [], $prepareOptions = []) {
        $sql = trim($sql);

        try {
            $query = $this->_connection->prepare($sql, $prepareOptions);
            $query->setFetchMode(PDO::FETCH_LAZY);
            if (!$query->execute($params)) {
                $this->_results = $query;
                $query->closeCursor();
                return false;
            }
            if (!$query->columnCount()) {
                $query->closeCursor();
                if (!$query->rowCount()) {
                    return true;
                }
            }

            return $query;
        } catch (PDOException $e) {
            if (isset($query->queryString)) {
                $e->queryString = $query->queryString;
            } else {
                $e->queryString = $sql;
            }
            throw $e;
        }
    }

    /**
     * Returns a row from current resultset as an array
     *
     * @param string $sql Some SQL to be executed.
     * @return array The fetched row as an array
     */
    public function fetchRow($sql = null) {
        if (is_string($sql) && strlen($sql) > 5 && !$this->execute($sql)) {
            return null;
        }

        if ($this->hasResult()) {
            $this->resultSet($this->_result);
            $resultRow = $this->fetchResult();

            return $resultRow;
        }

        return null;
    }

    public function getColumn($table = '') {
        $sql = "DESCRIBE {$table};";

        $query = $this->_connection->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function boolean($data, $quote = false) {
        if ($quote) {
            return !empty($data) ? '1' : '0';
        }
        return !empty($data);
    }

    public function value($data, $column = null) {
        switch ($column) {
            case 'binary':
                return $this->_connection->quote($data, PDO::PARAM_LOB);
            case 'boolean':
                return $this->_connection->quote($this->boolean($data, true), PDO::PARAM_BOOL);
            case 'string':
            case 'text':
                return $this->_connection->quote($data, PDO::PARAM_STR);
            default:
                if ($data === '') {
                    return 'NULL';
                }
                if (is_float($data)) {
                    return strtr(strval($data), array(',' => '.'));
                }

                if ((is_int($data) || $data === '0') || (
                        is_numeric($data) && strpos($data, ',') === false &&
                        $data[0] != '0' && strpos($data, 'e') === false)
                ) {
                    return $data;
                }

                return $this->_connection->quote($data);
        }
    }
}