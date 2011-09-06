<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\DataFixtures\Dumper;

use Symfony\Component\Yaml\Yaml;

/**
 * YAML fixtures dumper.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class YamlDataDumper extends AbstractDataDumper
{
    /**
     * {@inheritdoc}
     */
    abstract protected function transformArrayToData($data)
    {
        return Yaml::dump($data, 3);
    }

    /**
     * {@inheritdoc}
     */
    abstract protected function getFileExtension()
    {
        return 'yml';
    }
}