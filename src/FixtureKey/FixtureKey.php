<?php

namespace Fixturify\FixtureKey;

class FixtureKey
{
    private $key, $namespace, $path, $unique, $cloneIndex;

    /**
     * @param      $key
     * @param bool $withUnique
     */
    public function __construct($key, bool $withUnique = true)
    {
        if(strpos($key, ':') !== false) {
            [$mainKey, $cloneIndex] = explode(':', $key);
            $this->cloneIndex = $cloneIndex;
        }else{
            $mainKey = $key;
        }


        $this->key = $key;
        $parts     = explode('.', $mainKey);

        if ($withUnique) {
            $this->unique = array_pop($parts);
        }

        $this->namespace = implode('.', $parts);
        $this->path   = implode('/', $parts);
    }

    /**
     * @return mixed
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param $directory
     * @return string
     */
    public function getFullPath($directory): string
    {
        return sprintf('%s/%s', $directory, $this->path);
    }


    public function __toString()
    {
        return (string)$this->getKey();
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return mixed|string
     */
    public function getCloneIndex()
    {
        return $this->cloneIndex;
    }

}