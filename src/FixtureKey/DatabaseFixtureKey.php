<?php

namespace Fixturify\FixtureKey;

class DatabaseFixtureKey extends FixtureKey
{

    private $database, $table;

    public function __construct($key, $withUnique = true)
    {
        parent::__construct($key, $withUnique);

        $parts = explode('.', $key);
        if ($withUnique) {
            $unique = array_pop($parts);
        }
        $this->table = array_pop($parts);
        $this->database = array_pop($parts);
    }

    /**
     * @return mixed|null
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return mixed|null
     */
    public function getTable()
    {
        return $this->table;
    }

    public function getFullTableName(): string
    {
        return sprintf('%s.%s', $this->database, $this->table);
    }

}