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

namespace Doctrine\Bundle\CouchDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\CouchDBBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private $config;
    private $processor;

    public function setUp()
    {
        $this->config = new Configuration(false);
        $this->processor = new Processor();
    }

    public function testEmptyConfig()
    {
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            'The child node "client" at path "doctrine_couch_db" must be configured.');
        $config = $this->processor->processConfiguration($this->config, array());
    }

    public function testEmptyClientLeadsToDefaultConnection()
    {
        $config = $this->processor->processConfiguration($this->config, array(array('client' => null)));

        $this->assertEquals(array(
            'client' => array('default_connection' => 'default', 'connections' => array(
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false, 'url' => null)
            ))
        ), $config);
    }

    public function testSingleTopLevelClientConnectionDefinition()
    {
        $config = $this->processor->processConfiguration($this->config, array(
            array(
                'client' => array(),
            )
        ));

        $this->assertEquals(array(
            'client' => array('default_connection' => 'default', 'connections' => array(
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false, 'url' => null)
            ))
        ), $config);
    }

    public function testSingleTopLevelClientRenamedConnectionDefinition()
    {
        $config = $this->processor->processConfiguration($this->config, array(
            array(
                'client' => array('default_connection' => 'test'),
            )
        ));

        $this->assertEquals(array(
            'client' => array('default_connection' => 'test', 'connections' => array(
                'test' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false, 'url' => null)
            ))
        ), $config);
    }

    public function testMultipleClientConnections()
    {
        $config = $this->processor->processConfiguration($this->config, array(
            array(
                'client' => array('default_connection' => 'test', 'connections' => array(
                    'test' => array('port' => 4000),
                    'test2' => array('port' => 1984),
                )),
            )
        ));

        $this->assertEquals(array(
            'client' => array('default_connection' => 'test', 'connections' => array(
                'test' => array('port' => 4000, 'host' => 'localhost', 'user' => null, 'password' => null, 'ip' => null, 'logging' => false, 'url' => null),
                'test2' => array('port' => 1984, 'host' => 'localhost', 'user' => null, 'password' => null, 'ip' => null, 'logging' => false, 'url' => null)
            ))
        ), $config);
    }

    public function testSingleTopLevelDocumentManager()
    {
        $config = $this->processor->processConfiguration($this->config, array(
            array(
                'client' => array(),
                'odm' => array()
            )
        ));

        $this->assertEquals(array(
            'client' => array('default_connection' => 'default', 'connections' => array(
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false, 'url' => null)
            )),
            'odm' => array(
                'default_document_manager' => 'default',
                'document_managers' => array(
                    'default' => array(
                        'metadata_cache_driver' => array('type' => 'array', 'namespace' => null),
                        'auto_mapping' => false,
                        'mappings' => array(),
                        'design_documents' => array(),
                        'lucene_handler_name' => false,
                        'uuid_buffer_size' => 20,
                        'view_name' => 'symfony',
                        'all_or_nothing_flush' => true,
                    ),
                ),
                'auto_generate_proxy_classes' => false,
                'proxy_dir' => '%kernel.cache_dir%/doctrine/CouchDBProxies',
                'proxy_namespace' => 'CouchDBProxies',
            )
        ), $config);
    }

    public function testMultipleDocumentManager()
    {
        $config = $this->processor->processConfiguration($this->config, array(
            array(
                'client' => array(),
                'odm' => array(
                    'default_document_manager' => 'test',
                    'document_managers' => array('test' => array('connection' => 'default'), 'test2' => array())
                )
            )
        ));

        $this->assertEquals(array(
            'client' => array('default_connection' => 'default', 'connections' => array(
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false, 'url' => null)
            )),
            'odm' => array(
                'default_document_manager' => 'test',
                'document_managers' => array(
                    'test' => array(
                        'connection' => 'default',
                        'metadata_cache_driver' => array('type' => 'array', 'namespace' => null),
                        'auto_mapping' => false,
                        'mappings' => array(),
                        'design_documents' => array(),
                        'lucene_handler_name' => false,
                        'uuid_buffer_size' => 20,
                        'view_name' => 'symfony',
                        'all_or_nothing_flush' => true,
                    ),
                    'test2' => array(
                        'metadata_cache_driver' => array('type' => 'array', 'namespace' => null),
                        'auto_mapping' => false,
                        'mappings' => array(),
                        'design_documents' => array(),
                        'lucene_handler_name' => false,
                        'uuid_buffer_size' => 20,
                        'view_name' => 'symfony',
                        'all_or_nothing_flush' => true,
                    ),
                ),
                'auto_generate_proxy_classes' => false,
                'proxy_dir' => '%kernel.cache_dir%/doctrine/CouchDBProxies',
                'proxy_namespace' => 'CouchDBProxies',
            )
        ), $config);
    }
}
