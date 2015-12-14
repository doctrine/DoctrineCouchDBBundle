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

namespace Doctrine\Bundle\CouchDBBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for the CouchDB extension
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * Constructor
     *
     * @param Boolean $debug Whether to use the debug mode
     */
    public function  __construct($debug)
    {
        $this->debug = (Boolean) $debug;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('doctrine_couch_db');

        $this->addClientSection($rootNode);
        $this->addOdmSection($rootNode);

        return $treeBuilder;
    }

    private function addClientSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
            ->arrayNode('client')
                ->isRequired()
                ->beforeNormalization()
                    ->ifTrue(function ($v) { return !is_array($v) || (is_array($v) && !array_key_exists('connections', $v) && !array_key_exists('connection', $v)); })
                    ->then(function ($v) {
                        if (!is_array($v)) {
                            $v = array();
                        }

                        $connection = array();
                        foreach (array(
                            'dbname',
                            'host',
                            'port',
                            'user',
                            'password',
                            'ip',
                            'logging',
                            'type',
                            'url'
                        ) as $key) {
                            if (array_key_exists($key, $v)) {
                                $connection[$key] = $v[$key];
                                unset($v[$key]);
                            }
                        }
                        $v['default_connection'] = isset($v['default_connection']) ? (string) $v['default_connection'] : 'default';
                        $v['connections'] = array($v['default_connection'] => $connection);

                        return $v;
                    })
                ->end()
                ->children()
                    ->scalarNode('default_connection')->end()
                ->end()
                ->fixXmlConfig('connection')
                ->append($this->getClientConnectionsNode())
            ->end()
        ;
    }

    private function getClientConnectionsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('connections');

        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('dbname')->end()
                    ->scalarNode('host')->defaultValue('localhost')->end()
                    ->scalarNode('port')->defaultValue(5984)->end()
                    ->scalarNode('user')->defaultNull()->end()
                    ->scalarNode('password')->defaultNull()->end()
                    ->scalarNode('ip')->defaultNull()->end()
                    ->scalarNode('url')->defaultNull()->end()
                    ->booleanNode('logging')->defaultValue($this->debug)->end()
                    ->scalarNode('type')->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function addOdmSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('odm')
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return null === $v || (is_array($v) && !array_key_exists('document_managers', $v) && !array_key_exists('document_manager', $v)); })
                        ->then(function ($v) {
                            $v = (array) $v;
                            $documentManagers = array();
                            foreach (array(
                                'metadata_cache_driver', 'metadata-cache-driver',
                                'auto_mapping', 'auto-mapping',
                                'mappings', 'mapping',
                                'connection',
                            ) as $key) {
                                if (array_key_exists($key, $v)) {
                                    $documentManagers[$key] = $v[$key];
                                    unset($v[$key]);
                                }
                            }
                            $v['default_document_manager'] = isset($v['default_document_manager']) ? (string) $v['default_document_manager'] : 'default';
                            $v['document_managers'] = array($v['default_document_manager'] => $documentManagers);

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->scalarNode('default_document_manager')->end()
                        ->booleanNode('auto_generate_proxy_classes')->defaultFalse()->end()
                        ->scalarNode('proxy_dir')->defaultValue('%kernel.cache_dir%/doctrine/CouchDBProxies')->end()
                        ->scalarNode('proxy_namespace')->defaultValue('CouchDBProxies')->end()
                    ->end()
                    ->fixXmlConfig('document_manager')
                    ->append($this->getOdmDocumentManagersNode())
                ->end()
            ->end()
        ;
    }

    private function getOdmDocumentManagersNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('document_managers');

        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->addDefaultsIfNotSet()
                ->append($this->getOdmCacheDriverNode('metadata_cache_driver'))
                ->children()
                    ->scalarNode('connection')->end()
                    ->scalarNode('auto_mapping')->defaultFalse()->end()
                ->end()
                ->fixXmlConfig('mapping')
                ->fixXmlConfig('design_document')
                ->children()
                    ->arrayNode('mappings')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function($v) { return array('type' => $v); })
                            ->end()
                            ->treatNullLike(array())
                            ->treatFalseLike(array('mapping' => false))
                            ->performNoDeepMerging()
                            ->children()
                                ->scalarNode('mapping')->defaultValue(true)->end()
                                ->scalarNode('type')->end()
                                ->scalarNode('dir')->end()
                                ->scalarNode('alias')->end()
                                ->scalarNode('prefix')->end()
                                ->booleanNode('is_bundle')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('design_documents')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->treatNullLike(array())
                            ->children()
                                ->scalarNode('className')->end()
                                ->variableNode('options')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('lucene_handler_name')->defaultFalse()->end()
                    ->scalarNode('uuid_buffer_size')->defaultValue(20)->end()
                    ->scalarNode('view_name')->defaultValue('symfony')->end()
                    ->booleanNode('all_or_nothing_flush')->defaultTrue()->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function getOdmCacheDriverNode($name)
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root($name);

        $node
            ->addDefaultsIfNotSet()
            ->beforeNormalization()
                ->ifString()
                ->then(function($v) { return array('type' => $v); })
            ->end()
            ->children()
                ->scalarNode('type')->defaultValue('array')->isRequired()->end()
                ->scalarNode('host')->end()
                ->scalarNode('port')->end()
                ->scalarNode('instance_class')->end()
                ->scalarNode('class')->end()
                ->scalarNode('namespace')->defaultNull()->end()
            ->end()
        ;

        return $node;
    }
}
