<?php

namespace Fixturify\Generator;

use Fixturify\FixtureField\Name;
use Fixturify\FixtureKey\DatabaseFixtureKey;
use DomainException;
use RuntimeException;

class Php extends Base
{

    protected function getReaderClass(): string
    {
        return \Fixturify\Reader\Php::class;
    }

    public function getFileExtension(): string
    {
        return 'php';
    }

    public function generateFixtureFile($fixtureKey):void
    {
        $fixtureKey      = new DatabaseFixtureKey($fixtureKey, false);
        $fixtureFilePath = $this->_getFixtureFilePath($fixtureKey);

        if (file_exists($fixtureFilePath)) {
            $fixtureFilePath = realpath($fixtureFilePath);
            throw new RuntimeException("Fixture file $fixtureFilePath already exists " . $fixtureKey);
        }

        // Build the SQL SELECT statement
        $tableName = $fixtureKey->getFullTableName();

        $record = $this->storage->getAnyOne($tableName);

        // empty table - fill with nulls
        if(!$record){
            $record = array_fill_keys($this->storage->getSchema($tableName), null);
        }

        $primaryKeys = $this->storage->getPrimaryKeys($tableName);
        foreach ($primaryKeys as $primaryKey) {
            $record[$primaryKey] = null;
        }
        $records = [$record];

        $this->_writeToFile($fixtureFilePath, $records);
    }

    public function updateSchema($fixtureKey):void
    {
        $fixtureKey = new DatabaseFixtureKey($fixtureKey, false);

        $allFixtures = $this->reader->readAll($fixtureKey, true);
        if (!$allFixtures) {
            return;
        }

        $tableName = $fixtureKey->getFullTableName();
        $schema    = $this->storage->getSchema($tableName);
        if (!$schema) {
            throw new DomainException('Enable to get schema for the ' . $tableName);
        }

        $one = $this->storage->getAnyOne($tableName);

        // empty table - fill with nulls
        if(!$one){
            $one = array_fill_keys($this->storage->getSchema($tableName), null);
        }

        foreach (array_keys($allFixtures) as $fixtureUnique) {
            $fixtureSchema        = array_keys($allFixtures[$fixtureUnique]);
            $fixtureSchemaToCheck = array_diff($fixtureSchema, Name::SPECIAL_NAMES);

            $fieldsToRemove = array_diff($fixtureSchemaToCheck, $schema);
            $fieldsToAdd    = array_diff($schema, $fixtureSchemaToCheck);

            if (!$fieldsToAdd && !$fieldsToRemove) {
                continue;
            }

            // _dependency and so on fields in the fixture
            $fixtureSpecialFieldValues = array_intersect_key(
                $allFixtures[$fixtureUnique],
                array_fill_keys(Name::SPECIAL_NAMES, null)
            );

            $allFixtures[$fixtureUnique] =

                // REMOVE fields that were removed in the database
                array_intersect_key($allFixtures[$fixtureUnique], $one)

                // ADD NEW fields that are present in the database
                + $one

                // RESTORE special fields like _dependencies
                + $fixtureSpecialFieldValues;
        }

        $this->_writeToFile($this->_getFixtureFilePath($fixtureKey), $allFixtures);
    }

    private function _getFixtureFileHeader(): string
    {
        return '<?php
/**
 * @var \Faker\Generator $faker
 */

use function Fixturify\dependency;

return 
';
    }

    private function fixturesToString($fixtures)
    {
        $str = "\t[\n";
        foreach ($fixtures as $key => $fixture) {
            $str .= $this->fixtureToString($key, $fixture);
        }
        $str .= "\t]\n";

        // Unmute signatures
        return str_replace(["'%%%", "%%%',"], ['', ''], $str);
    }

    private function fixtureToString($key, $fixture): string
    {
        // length of the "array columns sorting"
        $maxFieldLength = 0;
        foreach (array_keys($fixture) as $field) {
            if (strlen($field) > $maxFieldLength) {
                $maxFieldLength = strlen($field);
            }
        }
        $maxFieldLength += 3; // additional margin for two ' and space before =>

        // fixture name and open array brackets
        $str = sprintf("\t\t'%s' => [\n", $key);
        foreach ($fixture as $field => $value) {
            // additional line before _dependencies block
            if ($field === Name::DEPENDENCIES) {
                $str .= "\n";
            }

            // write field name
            $str .= sprintf("\t\t\t%s => ", str_pad("'" . $field . "'", $maxFieldLength));

            // write value
            $str .= $this->writeValue($value) . ",\n";
        }

        // close array brackets
        $str .= "\t\t],\n";


        return $str;
    }

    private function writeValue($value): string
    {
        // _not empty array
        if (is_array($value) && !empty($value)) {
            $str = "[\n";
            foreach ($value as $v) {
                $str .= sprintf("\t\t\t\t%s,\n", $this->writeValue($v));
            }
            $str .= "\t\t\t]";
            return $str;
        }

        // empty array
        if (is_array($value)) {
            return '[ ]';
        }

        // null
        if (is_null($value)) {
            return 'null';
        }

        // any
        return "'$value'";
    }

    /**
     * @param array $records
     * @return string
     */
    protected function _fixturesExportString(array $records): string
    {
        return $this->_getFixtureFileHeader() . $this->fixturesToString($records) . ';';
    }

}