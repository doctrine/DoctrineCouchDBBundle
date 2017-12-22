<?php


namespace Doctrine\Bundle\CouchDBBundle\Tests;

use Doctrine\Bundle\CouchDBBundle\DoctrineCouchDBBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class BundleTest extends TestCase
{
    public function testRegisterCompilerPasses()
    {
        $bundle = new DoctrineCouchDBBundle();
        $builder = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder', array('hasExtension', 'addCompilerPass'));
        $builder->expects($this->at(0))->method('hasExtension')->will($this->returnValue(false));

        $builder->expects($this->at(1))
                ->method('addCompilerPass')
                ->with(
                    $this->isInstanceOf('Doctrine\Bundle\CouchDBBundle\DependencyInjection\Compiler\RegisterEventListenersAndSubscribersPass'),
                    $this->equalTo(PassConfig::TYPE_BEFORE_OPTIMIZATION)
                );
        $builder->expects($this->at(2))
                ->method('addCompilerPass')
                ->with(
                    $this->isInstanceOf('Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass'),
                    $this->equalTo(PassConfig::TYPE_BEFORE_OPTIMIZATION)
                );

        $bundle->build($builder);
    }
}
