<?php

namespace Fixturify\Reader;

use Fixturify\FixtureField\Name;
use Fixturify\FixtureKey\DatabaseFixtureKey;
use InvalidArgumentException;

class Php extends Base
{
    public const MUTE_FUNCTIONS = ['dependency', 'date', 'implode', 'strtotime', 'bin2hex'];
    public const MUTE_VARIABLES = ['faker'];

    /**
     * @var mixed
     */
    private $loadedFixturesRow = [];

    /**
     * @param DatabaseFixtureKey $fixtureKey
     * @return mixed
     */
    public function read(DatabaseFixtureKey $fixtureKey)
    {
        $allFixtures = $this->readAll($fixtureKey);

        if (!isset($allFixtures[$fixtureKey->getUnique()])) {
            throw new InvalidArgumentException('Fixture ' . $fixtureKey . ' is not found');
        }

        $fixture = $allFixtures[$fixtureKey->getUnique()];

        if(!isset($fixture[Name::DEPENDENCIES])){
            $fixture[Name::DEPENDENCIES] = [];
        }

        return $fixture;
    }

    protected function _getFixtureFilePath(DatabaseFixtureKey $fixtureKey): string
    {
        return sprintf('%s/%s.php', $this->directoryWithFixtures, $fixtureKey->getPath());
    }

    public function readAll(DatabaseFixtureKey $fixtureKey, $muteCode = false)
    {
        $fixtureFilePath = $this->_getFixtureFilePath($fixtureKey);

        if (!isset($this->loadedFixturesRow[$fixtureFilePath])) {
            $this->loadedFixturesRow[$fixtureFilePath] = $this->_readAll($fixtureKey, $muteCode);
        }

        return $this->loadedFixturesRow[$fixtureFilePath];
    }

    private function _getMutedFixtureFilePath(DatabaseFixtureKey $fixtureKey): string
    {
        return sprintf('%s/%s.m.php', $this->directoryWithFixtures, $fixtureKey->getPath());
    }

    /**
     * @param DatabaseFixtureKey $fixtureKey
     * @param bool               $muteCode
     * @return array
     */
    private function _readAll(DatabaseFixtureKey $fixtureKey, bool $muteCode = false): array
    {
        $fixtureFilePath = $this->_getFixtureFilePath($fixtureKey);

        if (!file_exists($fixtureFilePath)) {
            throw new \RuntimeException(
                'Fixture ' . $fixtureKey->getFullTableName() . ' file ' . $fixtureFilePath . ' does not exists'
            );
        }

        // Mute code
        if ($muteCode) {
            $fixtureFileContent = file_get_contents($fixtureFilePath);

            // ... mute allowed functions
            $stringsToMute = [];
            foreach (self::MUTE_FUNCTIONS as $muteFunction) {
                preg_match_all('/(' . $muteFunction . '\(.*(\w|\))\s?,)/im', $fixtureFileContent, $matches);
                $stringsToMute[] = reset($matches);
            }
            // ... mute allowed variables
            foreach (self::MUTE_VARIABLES as $muteVariable) {
                preg_match_all('/(\$' . $muteVariable . '->.*(\w|\)),)/im', $fixtureFileContent, $matches);
                $stringsToMute[] = reset($matches);
            }

            // ... muting and store into the temp file
            $stringsToMute = array_merge(...$stringsToMute);
            if ($stringsToMute) {
                $stringsToMute = array_unique($stringsToMute);

                $mutedStrings = array_map(static function ($stringToMute) {
                    $mutedString = str_replace("'", '"', $stringToMute);
                    return "'%%%" . $mutedString . "%%%',";
                }, $stringsToMute);

                $fixtureFileContent   = str_replace($stringsToMute, $mutedStrings, $fixtureFileContent);
                $mutedFixtureFilePath = $this->_getMutedFixtureFilePath($fixtureKey);

                file_put_contents($mutedFixtureFilePath, $fixtureFileContent);

                $fixtureFilePath = $mutedFixtureFilePath;
            }
        }


        $faker       = $this->faker;
        return include $fixtureFilePath;
    }
}