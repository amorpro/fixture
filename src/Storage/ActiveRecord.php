<?php

namespace Fixturify\Storage;

use ActiveRecord\db\Connection;
use ActiveRecord\db\Exception;
use ActiveRecord\db\TableSchema;
use ActiveRecord\db\Transaction;
use InvalidArgumentException;

class ActiveRecord extends StorageContract
{
    /** @var Connection */
    private $db;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function insert($table, $dataToInsert)
    {
        return $this->db
            ->schema->insert($table, $dataToInsert);
    }

    /**
     * @throws Exception
     */
    public function getAnyOne($tableName)
    {
        $sql = "SELECT * FROM $tableName LIMIT 1";
        return $this->db->createCommand($sql)->queryOne();
    }

    public function getPrimaryKeys(string $table): array
    {
        return $this->_getTableSchema($table)->primaryKey;
    }


    public function getSchema($table)
    {
        return $this->_getTableSchema($table)->getColumnNames();
    }

    public function load():void
    {
        $this->transaction = $this->db->beginTransaction();
    }

    /**
     * @throws Exception
     */
    public function unload():void
    {
        if($this->transaction) {
            $this->transaction->rollBack();
        }
    }

    /**
     * @param $table
     * @return TableSchema
     */
    private function _getTableSchema($table): TableSchema
    {
        $tableSchema = $this->db->getTableSchema($table);
        if (!$tableSchema) {
            throw new InvalidArgumentException('Enable to get table schema for table ' . $table);
        }
        return $tableSchema;
    }
}