<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\Command;

use Propel\Bundle\PropelBundle\Command\GeneratorAwareCommand;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
final class GeneratorAwareCommandTest extends TestCase
{
    public function testGetDatabasesFromSchema()
    {
        $kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->onlyMethods(array('getProjectDir', 'registerBundles', 'registerContainerConfiguration'))
            ->getMock();

        $kernel->method('getProjectDir')
            ->willReturn('.');

        $fileLocator = $this
            ->getMockBuilder('Symfony\Component\Config\FileLocator')
            ->getMock();

        $parameterBag = new ParameterBag(
            [
                'kernel.debug' => false,
                'kernel.root_dir' => dirname(__DIR__),
                'kernel.project_dir' => __DIR__,
                'propel.path' => __DIR__.'/../../vendor/propel/propel1'
            ]
        );

        $buildProperties = $this
            ->getMockBuilder('Propel\Bundle\PropelBundle\DependencyInjection\Properties')
            ->getMock();
        $propelConfig = $this
            ->getMockBuilder('PropelConfiguration')
            ->getMock();

        $command = new GeneratorAwareCommandTestable(
            $kernel,
            $fileLocator,
            $parameterBag,
            $buildProperties,
            $propelConfig
        );

        $databases = $command->getDatabasesFromSchema(new \SplFileInfo(dirname(__DIR__).'/Fixtures/schema.xml'));

        $this->assertTrue(is_array($databases));

        foreach ($databases as $database) {
            $this->assertInstanceOf('\Database', $database);
        }

        $bookstore = $databases[0];
        $this->assertEquals(1, count($bookstore->getTables()));

        foreach ($bookstore->getTables() as $table) {
            $this->assertInstanceOf('\Table', $table);
        }
    }
}

class GeneratorAwareCommandTestable extends GeneratorAwareCommand
{
    public function getDatabasesFromSchema(\SplFileInfo $file, \XmlToAppData $transformer = null)
    {
        $this->loadPropelGenerator();

        return parent::getDatabasesFromSchema($file, $transformer);
    }
}
