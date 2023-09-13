<?php

namespace Fixturify\Reader;

use Fixturify\FixtureKey\DatabaseFixtureKey;
use Faker\Factory;

abstract class Base
{

    protected $directoryWithFixtures;

    protected $faker;

    /**
     * @param $directoryWithFixtures
     */
    public function __construct($directoryWithFixtures)
    {
        $this->faker =  Factory::create();
        $this->directoryWithFixtures = $directoryWithFixtures;
    }

    abstract public function read(DatabaseFixtureKey $fixtureKey);

    abstract public function readAll(DatabaseFixtureKey $fixtureKey);


    abstract protected function _getFixtureFilePath(DatabaseFixtureKey $fixtureKey);

}