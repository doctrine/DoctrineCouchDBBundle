<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Bundle\CouchDBBundle\Tests;

use Symfony\Bundle\DoctrineBundle\Annotations\IndexedReader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Doctrine\Bundle\CouchDBBundle\DependencyInjection\DoctrineCouchDBExtension;

class ContainerTest extends TestCase
{
    public function testContainer()
    {
        $container = $this->createYamlBundleTestContainer();
        $this->assertInstanceOf('Doctrine\CouchDB\CouchDBClient', $container->get('doctrine_couchdb.client.default_connection'));
        $this->assertInstanceOf('Doctrine\ODM\CouchDB\DocumentManager', $container->get('doctrine_couchdb.odm.test_document_manager'));
    }

    public function testAllOrNothingFlush()
    {
        $container = $this->createYamlBundleTestContainer();
        $dm = $container->get('doctrine_couchdb.odm.test_document_manager');

        $config = $dm->getConfiguration();

        $this->assertFalse($config->getAllOrNothingFlush());

    }

    public function createYamlBundleTestContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
            'kernel.bundles'     => array('YamlBundle' => 'Fixtures\Bundles\YamlBundle\YamlBundle'),
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.root_dir'    => __DIR__ . "/../../../../" // src dir
        )));

        require_once __DIR__.'/DependencyInjection/Fixtures/Bundles/YamlBundle/YamlBundle.php';

        $container->set('annotation_reader', new AnnotationReader());
        $loader = new DoctrineCouchDBExtension();
        $container->registerExtension($loader);
        $loader->load(array(
            array(
                'client' => array('dbname' => 'testdb'),
                'odm' => array(
                    'default_document_manager' => 'test',
                    'document_managers' => array(
                        'test' => array(
                            'connection' => 'default',
                            'mappings' => array(
                                'YamlBundle' => array(
                                    'type' => 'yml',
                                    'dir' => __DIR__ . "/DependencyInjection/Fixtures/Bundles/YamlBundle/Resources/config/doctrine",
                                    'prefix' => 'Fixtures\Bundles\YamlBundle\CouchDocument',
                                )
                            ),
                            'all_or_nothing_flush' => false,
                        )
                    )
                )
            )
        ), $container);

        $container->getCompilerPassConfig()->setOptimizationPasses(array(new ResolveDefinitionTemplatesPass()));
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
