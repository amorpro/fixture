<?php
/** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */

/** @noinspection PhpUndefinedVariableInspection */

namespace Fixturify\FixtureField\Modifier;

use Fixturify\Dependency;
use Fixturify\FixtureField\Name;
use Fixturify\FixtureKey\DatabaseFixtureKey;
use InvalidArgumentException;
use RuntimeException;

class RelatedDependencies extends Base
{
    private $fixtureRow;

    /**
     * @param $fixtureRow
     */
    public function __construct($fixtureRow)
    {
        $this->fixtureRow = $fixtureRow;
    }

    public function needToModify(DatabaseFixtureKey $fixtureKey, $field, $value): bool
    {
        return $field === Name::DEPENDENCIES;
    }

    protected function _modify(DatabaseFixtureKey $fixtureKey, $field, &$value): void
    {
        foreach ($value as $dependency) {
            if(!$dependency instanceof Dependency){
                throw new RuntimeException(
                    "Fixture $fixtureKey contains dependency $dependency which is not wrapped into dependency() function"
                );
            }

            /** @var Dependency $dependency */
            try {
                $dependencyKey = new DatabaseFixtureKey($dependency->getKey());

                $overrides = [];
                foreach ($dependency->getOn() as $key) {
                    $overrides[$key] = $this->fixtureRow[$key];
                }
                foreach ($dependency->getOverrides() as $overrideKey => $overrideValue) {
                    $overrides[$overrideKey] = $overrideValue;
                }
                $this->fixtureService->loadFixture($dependencyKey, $overrides);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException(
                    $e->getMessage() . ' called from fixture ' . $dependencyKey, 0,
                    $e
                );
            }
        }
    }
}