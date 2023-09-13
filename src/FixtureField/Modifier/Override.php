<?php

namespace Fixturify\FixtureField\Modifier;

use Fixturify\FixtureKey\DatabaseFixtureKey;

class Override extends Base
{
    private $overrides;

    /**
     * @param array $overrides
     */
    public function __construct(array $overrides)
    {
        $this->overrides = $overrides;
    }

    public function needToModify(DatabaseFixtureKey $fixtureKey, $field, $value): bool
    {
        return isset($this->overrides[$field]);
    }

    protected function _modify(DatabaseFixtureKey $fixtureKey, $field, &$value): void
    {
        $value = $this->overrides[$field];
    }

}