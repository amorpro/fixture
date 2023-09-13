<?php

namespace Fixturify;

use Closure;
use Fixturify\FixtureField\Modifier\Dependency as DependencyModifier;
use Fixturify\FixtureField\Modifier\Override;
use Fixturify\FixtureField\Modifier\RelatedDependencies;
use Fixturify\FixtureField\Name;
use Fixturify\FixtureField\Pipeline;
use Fixturify\FixtureKey\DatabaseFixtureKey;
use Fixturify\Generator\Php;
use Fixturify\Reader\Base;
use Fixturify\Storage\StorageContract;
use Faker\Factory;
use InvalidArgumentException;

function dependency($fixture, $on = null, $overrides = []): Dependency
{
    return (new Dependency($fixture, $on, $overrides));
}

class FixtureService
{

    /**
     * @var StorageContract
     */
    private $storage;

    /**
     * @var Base
     */
    private $reader;

    private $loadedFixtures = [];

    /**
     * @var Php
     */
    private $generator;

    public function __construct($directoryWithFixtures, StorageContract $storage)
    {
        $this->storage               = $storage;
        $this->generator             = new Php($storage, $directoryWithFixtures);
        // @REVIEW may be need to link somehow generator and reader
        $this->reader = new Reader\Php($directoryWithFixtures);
    }

    public function load(array $fixtures): void
    {
        $this->storage->load();

        $this->loadFixtures($fixtures);
    }

    public function unload(): void
    {
        $this->storage->unload();
    }

    private function loadFixtures(array $fixtures): void
    {
        foreach ($fixtures as $fixture) {
            $this->loadFixture($fixture);
        }
    }

    /**
     * @param       $fixture
     * @param array $overrides
     * @return array
     */
    public function loadFixture($fixture, array $overrides = []): array
    {
        $fixtureKey = new DatabaseFixtureKey((string)$fixture);

        $loadedFixture = $this->_memorize($fixtureKey, function () use ($fixtureKey, $overrides) {
            $fixtureRow = $this->_loadFixtureRow($fixtureKey);

            // Pre insert field modifiers
            (new Pipeline($this))
                ->pipe(new Override($overrides))
                ->pipe(new DependencyModifier())
                ->modify($fixtureKey, $fixtureRow);


            return $this->_insertFixture($fixtureKey, $fixtureRow);
        });

        // Post insert field modifiers
        (new Pipeline($this))
            ->pipe(new RelatedDependencies($loadedFixture))
            ->modify($fixtureKey, $loadedFixture);

        return $loadedFixture;
    }

    private function _memorize(DatabaseFixtureKey $fixtureKey, Closure $loader)
    {
        if (!isset($this->loadedFixtures[$fixtureKey->getKey()])) {
            $this->loadedFixtures[$fixtureKey->getKey()] = $loader();
        }

        return $this->loadedFixtures[$fixtureKey->getKey()];
    }

    public function get($fixtureKey)
    {
        if (!isset($this->loadedFixtures[$fixtureKey])) {
            throw new InvalidArgumentException(
                "Fixture $fixtureKey is not pre-loaded. Define fixture in the loadFixture method first"
            );
        }
        return $this->loadedFixtures[$fixtureKey];
    }

    private function _insertFixture(DatabaseFixtureKey $databaseFixtureKey, $fixtureData)
    {
        $table = $databaseFixtureKey->getFullTableName();

        $dataToInsert = array_diff_key($fixtureData, array_fill_keys(Name::SPECIAL_NAMES, null));

        $primaryKeyValues = $this->storage->insert($table, $dataToInsert);

        foreach ($primaryKeyValues as $key => $value) {
            $fixtureData[$key] = $value;
        }
        $fixtureData[Name::PRIMARY_KEYS] = $primaryKeyValues;

        return $fixtureData;
    }

    public function generateFixtureFile($fixtureKey): void
    {
        $this->generator->generateFixtureFile($fixtureKey);
    }

    public function updateFixtureFileSchema($fixtureKey): void
    {
        $this->generator->updateSchema($fixtureKey);
    }

    public function __destruct()
    {
        $this->unload();
    }

    /**
     * @param DatabaseFixtureKey $fixtureKey
     * @return mixed
     */
    private function _loadFixtureRow(DatabaseFixtureKey $fixtureKey)
    {
        return $this->reader->read($fixtureKey);
    }

    /**
     * @param Generator\Base $generator
     */
    public function setGenerator(Generator\Base $generator): void
    {
        $this->generator = $generator;
    }

    /**
     * @param Base $reader
     */
    public function setReader(Base $reader): void
    {
        $this->reader = $reader;
    }

}

