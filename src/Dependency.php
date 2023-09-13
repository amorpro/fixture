<?php

namespace Fixturify;

class Dependency
{
    private $key, $on, $overrides;

    /**
     * @param       $key
     * @param null  $on
     * @param array $overrides
     */
    public function __construct($key, $on = null, array $overrides = [])
    {
        $this->key = $key;
        if(!$on){
            $on = [];
        }

        if(!is_array($on)){
            $on = [$on];
        }
        $this->on = $on;
        $this->overrides = $overrides;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getOn(): ?array
    {
        return $this->on;
    }

    /**
     * @return array
     */
    public function getOverrides(): array
    {
        return $this->overrides;
    }
}