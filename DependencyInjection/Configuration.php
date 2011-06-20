<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        $rootNode = $treeBuilder->root('doctrine_couchdb');

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
                            'type'
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
                                'connection'
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
            ->end()
        ;

        return $node;
    }
}