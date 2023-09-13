<?php

namespace Fixturify\FixtureField;

class Name
{
    public const PRIMARY_KEYS = '_primary_keys';
    public const DEPENDENCIES = '_dependencies';

    public const SPECIAL_NAMES = [
        self::DEPENDENCIES,
        self::PRIMARY_KEYS,
    ];
}