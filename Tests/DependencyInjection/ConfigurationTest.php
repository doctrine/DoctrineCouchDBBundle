<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\CouchDBBundle\Tests\DependencyInjection;

use Symfony\Bundle\CouchDBBundle\DependencyInjection\Configuration;
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
            'The child node "client" at path "doctrine_couchdb" must be configured.');
        $config = $this->processor->processConfiguration($this->config, array());
    }

    public function testEmptyClientLeadsToDefaultConnection()
    {
        $config = $this->processor->processConfiguration($this->config, array(array('client' => null)));

        $this->assertEquals(array(
            'client' => array('default_connection' => 'default', 'connections' => array(
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false)
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
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false)
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
                'test' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false)
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
                'test' => array('port' => 4000, 'host' => 'localhost', 'user' => null, 'password' => null, 'ip' => null, 'logging' => false),
                'test2' => array('port' => 1984, 'host' => 'localhost', 'user' => null, 'password' => null, 'ip' => null, 'logging' => false)
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
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false)
            )),
            'odm' => array(
                'default_document_manager' => 'default',
                'document_managers' => array(
                    'default' => array(
                        'metadata_cache_driver' => array('type' => 'array'),
                        'auto_mapping' => false,
                        'mappings' => array(),
                        'design_documents' => array(),
                        'lucene_handler_name' => false,
                        'uuid_buffer_size' => 20,
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
                'default' => array('host' => 'localhost', 'port' => 5984, 'user' => null, 'password' => null, 'ip' => null, 'logging' => false)
            )),
            'odm' => array(
                'default_document_manager' => 'test',
                'document_managers' => array(
                    'test' => array(
                        'connection' => 'default',
                        'metadata_cache_driver' => array('type' => 'array'),
                        'auto_mapping' => false,
                        'mappings' => array(),
                        'design_documents' => array(),
                        'lucene_handler_name' => false,
                        'uuid_buffer_size' => 20,
                        'all_or_nothing_flush' => true,
                    ),
                    'test2' => array(
                        'metadata_cache_driver' => array('type' => 'array'),
                        'auto_mapping' => false,
                        'mappings' => array(),
                        'design_documents' => array(),
                        'lucene_handler_name' => false,
                        'uuid_buffer_size' => 20,
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