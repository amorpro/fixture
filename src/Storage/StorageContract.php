<?php

namespace Fixturify\Storage;

abstract class StorageContract
{
    abstract public function insert($table, $dataToInsert);


    abstract public function getAnyOne($tableName);

    abstract public function getPrimaryKeys(string $table);


    abstract public function getSchema($table);

    abstract public function load();

    abstract public function unload();

    public function __destruct()
    {
        $this->unload();
    }
}