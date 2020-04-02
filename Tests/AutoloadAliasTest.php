<?php

namespace Propel\Bundle\PropelBundle\Tests;

final class AutoloadAliasTest extends \PHPUnit\Framework\TestCase
{
    public function testOldNamespaceWorks()
    {
        $inflector = new \Propel\PropelBundle\Util\PropelInflector();

        static::assertInstanceOf('Propel\PropelBundle\Util\PropelInflector', $inflector);
        static::assertInstanceOf('Propel\Bundle\PropelBundle\Util\PropelInflector', $inflector);
    }
}
