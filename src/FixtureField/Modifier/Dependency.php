<?php

namespace Fixturify\FixtureField\Modifier;

use Fixturify\FixtureField\Name;
use Fixturify\FixtureKey\DatabaseFixtureKey;
use InvalidArgumentException;
use RuntimeException;

class Dependency extends Base
{

    public function needToModify(DatabaseFixtureKey $fixtureKey, $field, $value): bool
    {
        return $value instanceof \Fixturify\Dependency;
    }

    protected function _modify(DatabaseFixtureKey $fixtureKey, $field, &$value): void
    {
        $dependencyFixtureKey = new DatabaseFixtureKey($value->getKey());
        try {
            $dependencyFixture = $this->fixtureService->loadFixture($dependencyFixtureKey);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage() . ' called from fixture ' . $fixtureKey, 0,
                $e
            );
        }

        $primaryKeys = $dependencyFixture[Name::PRIMARY_KEYS];

        if (!count($primaryKeys) || count($primaryKeys) > 1) {
            throw new RuntimeException(
                'Unable to determine primary keys for fixture ' . $fixtureKey . ' dependency ' . $dependencyFixtureKey
            );
        }

        $value = reset($primaryKeys);
    }

}