<?php

namespace Fixturify\Generator;

use Fixturify\FixtureKey\DatabaseFixtureKey;
use Fixturify\Storage\StorageContract;
use RuntimeException;

abstract class Base
{
    protected $directoryWithFixtures;
    /**
     * @var StorageContract
     */
    protected $storage;
    /**
     * @var \Fixturify\Reader\Php
     */
    protected $reader;

    /**
     * @param StorageContract $storage
     * @param string          $directoryWithFixtures
     */
    public function __construct(StorageContract $storage, string $directoryWithFixtures)
    {
        $this->storage               = $storage;
        $this->directoryWithFixtures = $directoryWithFixtures;

        $readerClass                 = $this->getReaderClass();
        $this->reader                = new $readerClass($directoryWithFixtures);
    }

    abstract public function getFileExtension();

    abstract protected function getReaderClass();

    abstract public function generateFixtureFile($fixtureKey);

    abstract public function updateSchema($fixtureKey);

    abstract protected function _fixturesExportString(array $records): string;

    /**
     * @param string $fixtureFilePath
     * @return void
     */
    protected function _ensureDirectory(string $fixtureFilePath): void
    {
        $dir = dirname($fixtureFilePath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }

    /**
     * @param string $fixtureFilePath
     * @param array  $records
     * @return void
     */
    protected function _writeToFile(string $fixtureFilePath, array $records): void
    {
        $this->_ensureDirectory($fixtureFilePath);

        file_put_contents($fixtureFilePath, $this->_fixturesExportString($records));
    }

    /**
     * @param DatabaseFixtureKey $fixtureKey
     * @return string
     */
    protected function _getFixtureFilePath(DatabaseFixtureKey $fixtureKey): string
    {
        return $fixtureKey->getFullPath($this->directoryWithFixtures) . '.' . $this->getFileExtension();
    }
}