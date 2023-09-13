<?php


use Fixturify\Dependency;
use PHPUnit\Framework\TestCase;

class DependencyTest extends TestCase
{

    private $dependency;

    protected function setUp(): void
    {
        $this->dependency = new Dependency('sheer.account.rebeca', ['content_id'], ['user_id' => 123]);
    }

    public function testGetOn()
    {
        $this->assertEquals(['content_id'], $this->dependency->getOn());
    }

    public function testGetKey()
    {
        $this->assertEquals('sheer.account.rebeca', $this->dependency->getKey());
    }

    public function testGetOverrides()
    {
        $this->assertEquals(['user_id' => 123], $this->dependency->getOverrides());
    }
}
