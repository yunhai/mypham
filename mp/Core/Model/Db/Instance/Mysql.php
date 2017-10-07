<?php

namespace Mp\Core\Model\Db\Instance;
use Mp\Core\Model\Db\Dbo\Sql;
use \PDO as PDO;

class Mysql extends Sql {

    public $config = [];

    protected $_transactionNesting = 0;
    protected $_useNestedTransactions = false;
    protected $_transactionStarted = false;

/**
 * Reference to the PDO object connection
 *
 * @var PDO $_connection
 */
    protected $_connection = null;

/**
 * use alias for update and delete. Set to true if version >= 4.1
 *
 * @var boolean
 */
    protected $_useAlias = true;

    public function __construct($config) {
        $this->config = $config;
        parent::__construct($config);
    }

/**
 * Connects to the database using options in the given configuration array.
 *
 * MySQL supports a few additional options that other drivers do not:
 *
 * - `unix_socket` Set to the path of the MySQL sock file. Can be used in place
 *   of host + port.
 * - `ssl_key` SSL key file for connecting via SSL. Must be combined with `ssl_cert`.
 * - `ssl_cert` The SSL certificate to use when connecting via SSL. Must be
 *   combined with `ssl_key`.
 * - `ssl_ca` The certificate authority for SSL connections.
 *
 * @return boolean True if the database could be connected, else false
 * @throws MissingConnectionException
 */
    public function connect() {
        $this->connected = false;

        $config = $this->config;
        $flags = array(
            PDO::ATTR_PERSISTENT => $config['persistent'],
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        if (!empty($config['encoding'])) {
            $flags[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['encoding'];
        }
        if (!empty($config['ssl_key']) && !empty($config['ssl_cert'])) {
            $flags[PDO::MYSQL_ATTR_SSL_KEY] = $config['ssl_key'];
            $flags[PDO::MYSQL_ATTR_SSL_CERT] = $config['ssl_cert'];
        }
        if (!empty($config['ssl_ca'])) {
            $flags[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
        }
        if (empty($config['unix_socket'])) {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        } else {
            $dsn = "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
        }

        try {
            $this->_connection = new PDO(
                $dsn,
                $config['login'],
                $config['password'],
                $flags
            );
            $this->connected = true;
            if (!empty($config['settings'])) {
                foreach ($config['settings'] as $key => $value) {
                    $this->_execute("SET $key=$value");
                }
            }
        } catch (PDOException $e) {
            throw new InternalErrorException(array(
                'class' => get_class($this),
                'message' => $e->getMessage()
            ));
        }

        $this->_useAlias = (bool)version_compare($this->getVersion(), "4.1", ">=");

        return $this->connected;
    }

/**
 * Check whether the MySQL extension is installed/loaded
 *
 * @return boolean
 */
    public function enabled() {
        return in_array('mysql', PDO::getAvailableDrivers());
    }

    public function getVersion() {
        return $this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function disconnect() {
        unset($this->_connection);
    }

    public function resultSet($results) {
        $this->map = [];
        $numFields = $results->columnCount();
        $index = 0;

        while ($numFields-- > 0) {
            $column = $results->getColumnMeta($index);
            $k = empty($column['table']) ? 0 : $column['table'];
            $this->map[$index++] = array($k, $column['name']);
        }
    }

    public function fetchResult() {
        if ($row = $this->_result->fetch(PDO::FETCH_NUM)) {

            $resultRow = [];
            foreach ($this->map as $col => $meta) {
                list($table, $column) = $meta;
                $resultRow[$table][$column] = $row[$col];
            }

            return $resultRow;
        }

        $this->_result->closeCursor();
        return false;
    }

    public function buildQuery($query = [], $type = "select") {
        if ($type == 'select') {
            return $this->__buildFind($query);
        }

        if ($type == 'create') {
            return $this->__buildCreate($query);
        }

        if ($type == 'update') {
            return $this->__buildUpdate($query);
        }

        return '';
    }

    private function __buildFind($query) {
        $prefix = $this->config['prefix'];

        if (empty($query['select'])) {
            $query['select'] = '*';
        }

        $replace = $query['from'];

        foreach ($query['from'] as $table => $alias) {
            $query['from'] = $prefix . $table . ' AS `' . $alias . '`';

            break;
        }

        if (!empty($query['join'])) {
            foreach ($query['join'] as $join) {
                $replace[$join['table']] = $join['alias'];
                $table = $prefix . $join['table'];

                $condition = 'ON (' . trim($join['condition']) . ')';
                $extend = ' ' . $join['type'] . ' JOIN ' . $table . ' AS `' . $join['alias'] . '` ' . $condition;

                $query['from'] .= $extend;
            }

            unset($query['join']);
        }

        foreach (array('select', 'from', 'where', 'order') as $element) {
            if (empty($query[$element])) {
                continue;
            }

            foreach ($replace as $table => $alias) {
                $query[$element] = strtr($query[$element], array("{$alias}." => "`{$alias}`."));
            }
        }

        if (empty($query['page'])) {
            $query['page'] = 1;
        }

        if ($query['page'] > 1 && !empty($query['limit'])) {
            $query['offset'] = ($query['page'] - 1) * $query['limit'];
        }

        if (!empty($query['limit'])) {
            $page = (empty($query['page'])) ? 1 : $query['page'];
            $offset = ($page - 1) * $query['limit'];
            $query['limit'] = "{$offset}, {$query['limit']}";
        }

        return $query;
    }

    private function __buildCreate($query) {
        $dbFields = $this->getColumn($query['from']);

        $from = $this->config['prefix'] . $query['from'];

        $index = 0;
        $fields = '';
        $value = $ignore = [];

        foreach ($query['fields'] as $record) {
            if ($index++ == 0) {
                $tmp = array_keys($record);
                foreach ($tmp as $key) {
                    if (in_array($key, $dbFields)) {
                        $fields .= "`{$key}`,";
                    } else {
                        $ignore[] = $key;
                    }
                }

                $fields = trim($fields, ',');
            }

            foreach ($record as $key => $field) {
                if (in_array($key, $ignore)) {
                    unset($record[$key]);

                    continue;
                }

                $record[$key] = $this->__format($field);
            }

            $value[] = implode(', ', $record);
        }

        return compact('fields', 'value', 'from');
    }

    private function __format($value = '') {
        $special = array('NOW()');

        $value = trim($value);
        if (in_array($value, $special)) {
            return $value;
        }

        if (strpos($value, 'exp.') === 0) {
            return str_replace('exp.', '', $value);
        }

        return $this->value($value, 'string');
    }

    public function create($option) {
        extract($option);

        $main = "INSERT INTO {$from} ({$fields}) VALUES ";

        $run = array_chunk($value, 10, true);

        $query = '';
        foreach ($run as $value) {
            $query .= $main;
            foreach ($value as $record) {
                $query .= '(' . $record . '),';
            }

            $query = rtrim($query, ',') . ';';
        }

        return $query;
    }

    private function __buildUpdate($query, $option = []) {
        $prefix = $this->config['prefix'];
        foreach ($query['from'] as $table => $alias) {
            $dbFields = $this->getColumn($table);
        }

       $query['from'] = $prefix . $table . ' AS `' . $alias . '`';

        $fields = '';
        foreach ($query['fields'] as $key => $value) {
            if (in_array($key, $dbFields)) {
                $update = $this->__format($value);

                $fields .= "`{$key}` = {$update},";
            }
        }

        $fields = trim($fields, ',');

        $query['fields'] = $fields;

        return $query;
    }

    public function select($query = []) {
        $key = [
            'select' => 'SELECT',
            'from' => 'FROM',
            'where' => 'WHERE',
            'group' => 'GROUP BY',
            'having' => 'HAVING',
            'order' => 'ORDER BY',
            'limit' => 'LIMIT'
        ];

        $q = '';
        foreach ($key as $k => $v) {
            if (empty($query[$k])) {
                continue;
            }

            $q .= "{$v} {$query[$k]} ";
        }

        return trim($q);
    }

    public function update($query = []) {
        extract($query);

        return trim("UPDATE {$from} SET {$fields} WHERE {$where}");
    }

    /**
     * Returns the ID generated from the previous INSERT operation.
     *
     * @param mixed $source
     * @return mixed
     */
    public function lastInsertId() {
        return $this->_connection->lastInsertId();
    }

    public function getColumn($table = '') {
        $table = $this->config['prefix'] . $table;
        return parent::getColumn($table);
    }

    public function nestedTransactionSupported() {
        return $this->_useNestedTransactions && version_compare($this->getVersion(), '4.1', '>=');
    }

    public function nestedTransaction ($status = null) {
        if (isset($status)) {
            $this->_useNestedTransactions = $status;
        }

        return $this->_useNestedTransactions;
    }

    public function begin() {
        if ($this->_transactionStarted) {
            if ($this->nestedTransactionSupported()) {
                return $this->_beginNested();
            }
            $this->_transactionNesting++;
            return $this->_transactionStarted;
        }

        $this->_transactionNesting = 0;
        $this->logQuery('BEGIN');

        return $this->_transactionStarted = $this->_connection->beginTransaction();
    }

    protected function _beginNested() {
        $query = 'SAVEPOINT LEVEL' . ++$this->_transactionNesting;
        $this->logQuery($query);

        $this->_connection->exec($query);
        return true;
    }

    public function commit() {
        if (!$this->_transactionStarted) {
            return false;
        }

        if ($this->_transactionNesting === 0) {
            $this->logQuery('COMMIT');
            $this->_transactionStarted = false;
            return $this->_connection->commit();
        }

        if ($this->nestedTransactionSupported()) {
            return $this->_commitNested();
        }

        $this->_transactionNesting--;
        return true;
    }

    protected function _commitNested() {
        $query = 'RELEASE SAVEPOINT LEVEL' . $this->_transactionNesting--;
        $this->logQuery($query);

        $this->_connection->exec($query);
        return true;
    }

    public function rollback() {
        if (!$this->_transactionStarted) {
            return false;
        }

        if ($this->_transactionNesting === 0) {
            $this->logQuery('ROLLBACK');
            $this->_transactionStarted = false;
            return $this->_connection->rollBack();
        }

        if ($this->nestedTransactionSupported()) {
            return $this->_rollbackNested();
        }

        $this->_transactionNesting--;
        return true;
    }

    protected function _rollbackNested() {
        $query = 'ROLLBACK TO SAVEPOINT LEVEL' . $this->_transactionNesting--;
        $this->logQuery($query);
        $this->_connection->exec($query);
        return true;
    }
}