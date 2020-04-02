<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\Command;

use Propel\Bundle\PropelBundle\Command\AbstractCommand;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
final class AbstractCommandTest extends TestCase
{
    /**
     * @var TestableAbstractCommand
     */
    private $command;

    /**
     * @var \Symfony\Component\Config\FileLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileLocator;

    public function setUp(): void
    {
        $this->fileLocator = $this->createPartialMock('Symfony\Component\Config\FileLocator', ['locate']);

        $kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')
            ->getMock();
        $parameterBag = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface')
            ->getMock();
        $buildProperties = $this
            ->getMockBuilder('Propel\Bundle\PropelBundle\DependencyInjection\Properties')
            ->getMock();
        $propelConfig = $this->getMockBuilder('PropelConfiguration')
            ->getMock();

        $this->command = new TestableAbstractCommand(
            $kernel,
            $this->fileLocator,
            $parameterBag,
            $buildProperties,
            $propelConfig
        );
    }

    public function testParseDbName()
    {
        $dsn = 'mydsn#dbname=foo';
        $this->assertEquals('foo', $this->command->parseDbName($dsn));
    }

    public function testParseDbNameWithoutDbName()
    {
        $this->assertNull($this->command->parseDbName('foo'));
    }

    public function testTransformToLogicalName()
    {
        $bundleDir = realpath(__DIR__.'/../Fixtures/src/My/SuperBundle');
        $filename = 'Resources'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'a-schema.xml';

        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue($bundleDir));

        $schema = new \SplFileInfo($bundleDir.DIRECTORY_SEPARATOR.$filename);
        $expected = '@MySuperBundle/Resources/config/a-schema.xml';
        $this->assertEquals($expected, $this->command->transformToLogicalName($schema, $bundle));
    }

    public function testTransformToLogicalNameWithSubDir()
    {
        $bundleDir = realpath(__DIR__.'/../Fixtures/src/My/ThirdBundle');
        $filename = 'Resources'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'propel'.DIRECTORY_SEPARATOR.'schema.xml';

        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MyThirdBundle'));
        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue($bundleDir));

        $schema = new \SplFileInfo($bundleDir.DIRECTORY_SEPARATOR.$filename);
        $expected = sprintf('@MyThirdBundle/Resources/config/propel%sschema.xml', DIRECTORY_SEPARATOR);
        $this->assertEquals($expected, $this->command->transformToLogicalName($schema, $bundle));
    }

    public function testGetSchemasFromBundle()
    {
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue(__DIR__.'/../Fixtures/src/My/SuperBundle'));

        $aSchema = realpath(__DIR__.'/../Fixtures/src/My/SuperBundle/Resources/config/a-schema.xml');

        $this->fileLocator
            ->expects(self::atLeastOnce())
            ->method('locate')
            ->willReturn($aSchema);

        $schemas = $this->command->getSchemasFromBundle($bundle);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(1, $schemas);
        $this->assertArrayHasKey($aSchema, $schemas);
        $this->assertSame($bundle, $schemas[$aSchema][0]);
        $this->assertEquals(new \SplFileInfo($aSchema), $schemas[$aSchema][1]);
    }

    public function testGetSchemasFromBundleWithNoSchema()
    {
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue(__DIR__.'/../Fixtures/src/My/SecondBundle'));

        $schemas = $this->command->getSchemasFromBundle($bundle);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(0, $schemas);
    }

    public function testGetFinalSchemasWithNoSchemaInBundles()
    {
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')->getMock();

        $bundle
            ->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue(__DIR__.'/../Fixtures/src/My/SecondBundle'));

        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array($bundle)));

        $schemas = $this->command->getFinalSchemas($kernel);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(0, $schemas);
    }

    public function testGetFinalSchemas()
    {
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')->getMock();

        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue(__DIR__.'/../Fixtures/src/My/SuperBundle'));

        $aSchema = realpath(__DIR__.'/../Fixtures/src/My/SuperBundle/Resources/config/a-schema.xml');

        $this->fileLocator
            ->expects(self::atLeastOnce())
            ->method('locate')
            ->willReturn($aSchema);

        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array($bundle)));

        $schemas = $this->command->getFinalSchemas($kernel);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(1, $schemas);
        $this->assertArrayHasKey($aSchema, $schemas);
        $this->assertSame($bundle, $schemas[$aSchema][0]);
        $this->assertEquals(new \SplFileInfo($aSchema), $schemas[$aSchema][1]);
    }

    public function testGetFinalSchemasWithGivenBundle()
    {
        $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\BundleInterface')->getMock();
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')->getMock();

        $bundle
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('MySuperBundle'));
        $bundle
            ->expects($this->exactly(2))
            ->method('getPath')
            ->will($this->returnValue(__DIR__.'/../Fixtures/src/My/SuperBundle'));

        $aSchema = realpath(__DIR__.'/../Fixtures/src/My/SuperBundle/Resources/config/a-schema.xml');

        $this->fileLocator
            ->expects(self::atLeastOnce())
            ->method('locate')
            ->willReturn($aSchema);

        $kernel
            ->expects($this->never())
            ->method('getBundles');

        $schemas = $this->command->getFinalSchemas($kernel, $bundle);

        $this->assertNotNull($schemas);
        $this->assertTrue(is_array($schemas));
        $this->assertCount(1, $schemas);
        $this->assertArrayHasKey($aSchema, $schemas);
        $this->assertSame($bundle, $schemas[$aSchema][0]);
        $this->assertEquals(new \SplFileInfo($aSchema), $schemas[$aSchema][1]);
    }
}

class TestableAbstractCommand extends AbstractCommand
{
    public function parseDbName($dsn)
    {
        return parent::parseDbName($dsn);
    }

    public function transformToLogicalName(\SplFileInfo $schema, BundleInterface $bundle)
    {
        return parent::transformToLogicalName($schema, $bundle);
    }

    public function getSchemasFromBundle(BundleInterface $bundle)
    {
        return parent::getSchemasFromBundle($bundle);
    }

    public function getFinalSchemas(KernelInterface $kernel, BundleInterface $bundle = null)
    {
        return parent::getFinalSchemas($kernel, $bundle);
    }
}
