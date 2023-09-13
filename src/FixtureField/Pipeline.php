<?php

namespace Fixturify\FixtureField;

use Fixturify\FixtureField\Modifier\Base;
use Fixturify\FixtureKey\DatabaseFixtureKey;
use Fixturify\FixtureService;

class Pipeline
{
    /**
     * @var FixtureService
     */
    private $fixtureService;

    /**
     * @var Base[]
     */
    private $fixtureFieldModifiers = [];

    /**
     * @param FixtureService $fixtureService
     */
    public function __construct(FixtureService $fixtureService)
    {
        $this->fixtureService = $fixtureService;
    }

    /**
     * @param Base $modifier
     * @return $this
     */
    public function pipe(Base $modifier): self
    {
        $modifier
            ->setFixtureService($this->fixtureService)
            ->setPipeline($this)
        ;

        $this->fixtureFieldModifiers[] = $modifier;

        return $this;
    }

    public function modify(DatabaseFixtureKey $fixtureKey, array &$fixtureRow): void
    {
        foreach($fixtureRow as $field => &$value){
            foreach($this->fixtureFieldModifiers as $modifier){
                $modifier->modify($fixtureKey, $field, $value);
            }
        }

        unset($value);
    }

}