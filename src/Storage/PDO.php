<?php

namespace Fixturify\Storage;

class PDO extends StorageContract
{

    /** @var \PDO */
    private $db;

    private static $schema = [];
    /**
     * @var bool
     */
    private $transaction;

    /**
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function insert($table, $dataToInsert)
    {
        // remove auto increment columns from teh insert
        $autoIncrementColumns = $this->_getTableSchema($table)['auto_increment_keys'];
        foreach(array_keys($dataToInsert) as $column){
            if (!$dataToInsert[$column] && in_array($column, $autoIncrementColumns) ) {
                unset($dataToInsert[$column]);
            }
        }

        // Insert
        $columns = array_keys($dataToInsert);
        $columnsForBind = array_map(static function($column){ return ':'.$column; }, $columns);
        $values = array_values($dataToInsert);

        $sql = 'INSERT INTO ' . $table
            . (!empty($columns) ? ' (`' . implode('`, `', $columns) . '`)' : '')
            . (!empty($columnsForBind) ? ' VALUES (' . implode(', ', $columnsForBind) . ')' : $values);

        $stmt = $this->db->prepare($sql);
        foreach($columnsForBind as $k => $columnForBind) {
            $stmt->bindParam($columnForBind, $values[$k]);
        }
        if (!$stmt->execute()) {
            return false;
        }

        // last insert id

        $result = [];
        foreach ($this->getPrimaryKeys($table) as $column) {
            if (in_array($column, $autoIncrementColumns)) {
                $result[$column] = $this->db->lastInsertId($column);
                break;
            }
        }

        return $result;
    }

    /**
     */
    public function getAnyOne($tableName)
    {
        $stmt = $this->db->prepare("SELECT * FROM $tableName order by 1 DESC LIMIT 1");
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPrimaryKeys(string $table): array
    {
        return $this->_getTableSchema($table)['primary_keys'];
    }

    public function getSchema($table)
    {
        return $this->_getTableSchema($table)['columns'];
    }

    public function load():void
    {
        $this->transaction = $this->db->beginTransaction();
    }

    public function unload():void
    {
        if($this->transaction) {
            $this->db->rollBack();
        }
    }


    private function _getTableSchema($table)
    {
        if (isset(self::$schema[$table])) {
            return self::$schema[$table];
        }

        [$database, $tableName] = explode('.', $table);

        $query     = '
            SELECT COLUMN_NAME, COLUMN_KEY, EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :databaseName
            AND TABLE_NAME = :tableName
        ';
        $statement = $this->db->prepare($query);
        $statement->bindParam(':databaseName', $database);
        $statement->bindParam(':tableName', $tableName);
        $statement->execute();

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        self::$schema[$table] = [];
        foreach ($rows as $row) {
            self::$schema[$table]['columns'][] = $row['COLUMN_NAME'];

            if ($row['COLUMN_KEY'] === 'PRI') {
                self::$schema[$table]['primary_keys'][] = $row['COLUMN_NAME'];
            }
            if (strpos($row['EXTRA'], 'auto_increment') !== false) {
                self::$schema[$table]['auto_increment_keys'][] = $row['COLUMN_NAME'];
            }
        }

        return self::$schema[$table];
    }
}