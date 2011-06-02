<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\CouchDBBundle\Tests;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Doctrine\\Common\\Version') || !class_exists('Doctrine\\CouchDB\\Version')) {
            $this->markTestSkipped('Doctrine is not available.');
        }
    }
}