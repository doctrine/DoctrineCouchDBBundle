<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\CouchDBBundle\Tests;

use Doctrine\Bundle\CouchDBBundle\DoctrineCouchDBBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class BundleTest extends TestCase
{
    public function testRegisterCompilerPasses()
    {
        $bundle = new DoctrineCouchDBBundle();
        $builder = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        
        $builder->expects($this->at(0))
                ->method('addCompilerPass')
                ->with(
                    $this->isInstanceOf('Doctrine\Bundle\CouchDBBundle\DependencyInjection\Compiler\RegisterEventListenersAndSubscribersPass'),
                    $this->equalTo(PassConfig::TYPE_BEFORE_OPTIMIZATION)
                );
        $builder->expects($this->at(1))
                ->method('addCompilerPass')
                ->with(
                    $this->isInstanceOf('Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass'),
                    $this->equalTo(PassConfig::TYPE_BEFORE_OPTIMIZATION)
                );

        $bundle->build($builder);
    }
}