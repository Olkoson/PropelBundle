<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\Command;

use Propel\Bundle\PropelBundle\Command\DatabaseCreateCommand;
use Propel\Bundle\PropelBundle\Tests\TestCase;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
final class DatabaseCreateCommandTest extends TestCase
{
    /** @var TestableDatabaseCreateCommand */
    protected $command;

    public function setUp(): void
    {
        $kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')
            ->getMock();
        $fileLocator = $this
            ->getMockBuilder('Symfony\Component\Config\FileLocator')
            ->getMock();
        $parameterBag = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface')
            ->getMock();
        $buildProperties = $this
            ->getMockBuilder('Propel\Bundle\PropelBundle\DependencyInjection\Properties')
            ->getMock();
        $propelConfig = $this
            ->getMockBuilder('PropelConfiguration')
            ->getMock();

        $this->command = new TestableDatabaseCreateCommand(
            $kernel,
            $fileLocator,
            $parameterBag,
            $buildProperties,
            $propelConfig
        );
    }

    public function tearDown(): void
    {
        $this->command = null;
    }

    /**
     * @dataProvider dataTemporaryConfiguration
     */
    public function testTemporaryConfiguration($name, $config, $expectedDsn)
    {
        $datasource = $this->command->getTemporaryConfiguration($name, $config);

        $this->assertArrayHasKey('datasources', $datasource);
        $this->assertArrayHasKey($name, $datasource['datasources']);
        $this->assertArrayHasKey('connection', $datasource['datasources'][$name]);
        $this->assertArrayHasKey('dsn', $datasource['datasources'][$name]['connection']);
        $this->assertEquals($expectedDsn, $datasource['datasources'][$name]['connection']['dsn']);
    }

    public function dataTemporaryConfiguration()
    {
        return array(
            array(
                'dbname',
                array('connection' => array('dsn' => 'mydsn:host=localhost;dbname=test_db;')),
                'mydsn:host=localhost;'
            ),
            array(
                'dbname_first',
                array('connection' => array('dsn' => 'mydsn:dbname=test_db;host=localhost')),
                'mydsn:host=localhost'
            ),
            array(
                'dbname_no_semicolon',
                array('connection' => array('dsn' => 'mydsn:host=localhost;dbname=test_db')),
                'mydsn:host=localhost;'
            ),
            array(
                'no_dbname',
                array('connection' => array('dsn' => 'mydsn:host=localhost;')),
                'mydsn:host=localhost;'
            ),
        );
    }
}

class TestableDatabaseCreateCommand extends DatabaseCreateCommand
{
    public function getTemporaryConfiguration($name, $config)
    {
        return parent::getTemporaryConfiguration($name, $config);
    }
}
