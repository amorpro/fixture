<?php

namespace Fixturify\FixtureField\Modifier;

use Fixturify\FixtureField\Pipeline;
use Fixturify\FixtureKey\DatabaseFixtureKey;
use Fixturify\FixtureService;

abstract class Base
{
    /**
     * @var FixtureService
     */
    protected $fixtureService;

    /**
     * @var Pipeline
     */
    protected $pipeline;

    abstract public function needToModify(DatabaseFixtureKey $fixtureKey, $field, $value): bool;

    public function modify(DatabaseFixtureKey $fixtureKey, $field, &$value): void
    {
        if (!$this->needToModify($fixtureKey, $field, $value)) {
            return;
        }

        $this->_modify($fixtureKey, $field, $value);
    }

    /**
     * @param FixtureService $fixtureService
     * @return Base
     */
    public function setFixtureService(FixtureService $fixtureService): self
    {
        $this->fixtureService = $fixtureService;

        return $this;
    }

    abstract protected function _modify(DatabaseFixtureKey $fixtureKey, $field, &$value);

    /**
     * @param Pipeline $pipeline
     * @return Base
     */
    public function setPipeline(Pipeline $pipeline): self
    {
        $this->pipeline = $pipeline;
        return $this;
    }

}