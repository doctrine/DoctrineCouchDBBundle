<?php


namespace Doctrine\Bundle\CouchDBBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bridge\Doctrine\DependencyInjection\AbstractDoctrineExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

/**
 * CouchDB Extension
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DoctrineCouchDBExtension extends AbstractDoctrineExtension
{
    private $documentManagers;

    private $bundleDirs = array();

    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config = $processor->processConfiguration($configuration, $configs);

        if (!empty($config['client'])) {
            $this->clientLoad($config['client'], $container);
        }

        if (!empty($config['odm'])) {
            $this->odmLoad($config['odm'], $container);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    private function clientLoad($config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('client.xml');

        if (empty($config['default_connection'])) {
            $keys = array_keys($config['connections']);
            $config['default_connection'] = reset($keys);
        }
        $this->defaultConnection = $config['default_connection'];

        $container->setAlias('couchdb_connection', sprintf('doctrine_couchdb.client.%s_connection', $this->defaultConnection));

        $connections = array();
        foreach (array_keys($config['connections']) as $name) {
            $connections[$name] = sprintf('doctrine_couchdb.client.%s_connection', $name);
        }
        $container->setParameter('doctrine_couchdb.connections', $connections);
        $container->setParameter('doctrine_couchdb.default_connection', $this->defaultConnection);

        foreach ($config['connections'] as $name => $connection) {
            $this->loadClientConnection($name, $connection, $container);
        }
    }

    protected function loadClientConnection($name, array $connection, ContainerBuilder $container)
    {
        $container
            ->setDefinition(sprintf('doctrine_couchdb.client.%s_connection', $name), new DefinitionDecorator('doctrine_couchdb.client.connection'))
            ->setArguments(array(
                $connection,
            ))
        ;

        if (isset($connection['logging']) && $connection['logging'] === true) {
            $def = new Definition('Doctrine\CouchDB\HTTP\Client');
            $def->setFactory([new Reference(sprintf('doctrine_couchdb.client.%s_connection', $name)), 'getHttpClient']);
            $def->setPublic(false);

            $container->setDefinition(sprintf('doctrine_couchdb.httpclient.%s_client', $name), $def);

            $def = $container->getDefinition('doctrine_couchdb.datacollector');
            $def->addMethodCall('addLoggingClient', array(
                new Reference(sprintf('doctrine_couchdb.httpclient.%s_client', $name)),
                $name
            ));
        }
    }

    private function odmLoad($config, $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('odm.xml');

        $this->documentManagers = array();
        foreach (array_keys($config['document_managers']) as $name) {
            $this->documentManagers[$name] = sprintf('doctrine_couchdb.odm.%s_document_manager', $name);
        }
        $container->setParameter('doctrine_couchdb.document_managers', $this->documentManagers);

        if (empty($config['default_document_manager'])) {
            $tmp = array_keys($this->documentManagers);
            $config['default_document_manager'] = reset($tmp);
        }
        $container->setParameter('doctrine_couchdb.default_document_manager', $config['default_document_manager']);

        $options = array('auto_generate_proxy_classes', 'proxy_dir', 'proxy_namespace');
        foreach ($options as $key) {
            $container->setParameter('doctrine_couchdb.odm.'.$key, $config[$key]);
        }

        $container->setAlias('doctrine_couchdb.odm.document_manager', sprintf('doctrine_couchdb.odm.%s_document_manager', $config['default_document_manager']));

        foreach ($config['document_managers'] as $name => $documentManager) {
            $documentManager['name'] = $name;
            $this->loadOdmDocumentManager($documentManager, $container);
        }
    }

    private function loadOdmDocumentManager($documentManager, ContainerBuilder $container)
    {
        if ($documentManager['auto_mapping'] && count($this->documentManagers) > 1) {
            throw new \LogicException('You cannot enable "auto_mapping" when several CouchDB document managers are defined.');
        }

        $odmConfigDef = $container->setDefinition(sprintf('doctrine_couchdb.odm.%s_configuration', $documentManager['name']), new DefinitionDecorator('doctrine_couchdb.odm.configuration'));

        $this->loadOdmDocumentManagerMappingInformation($documentManager, $odmConfigDef, $container);
        $this->loadOdmDocumentManagerDesignDocuments($documentManager, $odmConfigDef);
        $this->loadOdmCacheDrivers($documentManager, $container);

        $methods = array(
            'setMetadataCacheImpl'        => new Reference(sprintf('doctrine_couchdb.odm.%s_metadata_cache', $documentManager['name'])),
            'setMetadataDriverImpl'       => new Reference('doctrine_couchdb.odm.'.$documentManager['name'].'_metadata_driver'),
            'setProxyDir'                 => '%doctrine_couchdb.odm.proxy_dir%',
            'setProxyNamespace'           => '%doctrine_couchdb.odm.proxy_namespace%',
            'setAutoGenerateProxyClasses' => '%doctrine_couchdb.odm.auto_generate_proxy_classes%',
        );
        foreach ($methods as $method => $arg) {
            $odmConfigDef->addMethodCall($method, array($arg));
        }

        $odmConfigDef->addMethodCall('setAllOrNothingFlush', array($documentManager['all_or_nothing_flush']));

        if (!isset($documentManager['connection'])) {
            $documentManager['connection'] = $this->defaultConnection;
        }

        $def = $container->setDefinition(sprintf('doctrine_couchdb.odm.%s_connection.event_manager', $documentManager['name']), new DefinitionDecorator('doctrine_couchdb.odm.document_manager.event_manager'));

        $container
            ->setDefinition(sprintf('doctrine_couchdb.odm.%s_document_manager', $documentManager['name']), new DefinitionDecorator('doctrine_couchdb.odm.document_manager.abstract'))
            ->setArguments(array(
                new Reference(sprintf('doctrine_couchdb.client.%s_connection', $documentManager['connection'])),
                new Reference(sprintf('doctrine_couchdb.odm.%s_configuration', $documentManager['name'])),
                new Reference(sprintf('doctrine_couchdb.odm.%s_connection.event_manager', $documentManager['name']))
            ))
        ;
    }

    protected function getMappingDriverBundleConfigDefaults(array $bundleConfig, \ReflectionClass $bundle, ContainerBuilder $container)
    {
        $this->bundleDirs[$bundle->getNamespaceName()] = dirname($bundle->getFileName());
        return parent::getMappingDriverBundleConfigDefaults($bundleConfig, $bundle, $container);
    }


    protected function loadOdmDocumentManagerMappingInformation(array $documentManager, Definition $odmConfig, ContainerBuilder $container)
    {
        // reset state of drivers and alias map. They are only used by this methods and children.
        $this->drivers = array();
        $this->aliasMap = array();
        $this->bundleDirs = array();

        $this->loadMappingInformation($documentManager, $container);
        $this->registerMappingDrivers($documentManager, $container);

        // This looks scary but essentially finds the right document manager for any registered/mapped bundle
        // and then registers any potential CouchDB views with that document manager.
        foreach ($this->aliasMap AS $alias => $prefix) {
            foreach ($this->bundleDirs AS $bundleNamespace => $bundleDir) {
                if (strpos($prefix, $bundleNamespace) === 0 && file_exists($bundleDir."/Resources/couchdb")) {
                    $it = new \DirectoryIterator($bundleDir."/Resources/couchdb");
                    foreach ($it AS $res) {
                        if ($res->isDir() && !$res->isDot()) {
                            $odmConfig->addMethodCall('addDesignDocument', array(
                                $res->getBasename(),
                                'Doctrine\CouchDB\View\FolderDesignDocument',
                                $bundleDir."/Resources/couchdb/" . $res->getBasename()
                            ));

                        }
                    }
                }
            }
        }


        $odmConfig->addMethodCall('setDocumentNamespaces', array($this->aliasMap));
    }

    /**
     * Loads configured design_documents.
     *
     * @param array $documentManager The document manager configuration.
     * @param Definition $odmConfig A service definition instance.
     */
    protected function loadOdmDocumentManagerDesignDocuments(array $documentManager, Definition $odmConfig)
    {
        foreach ($documentManager['design_documents'] as $name => $designDocument) {
            $odmConfig->addMethodCall(
                'addDesignDocument', array(
                    $name,
                    $designDocument['className'],
                    $designDocument['options']
                )
            );
        }
    }

    /**
     * Loads a configured document managers cache drivers.
     *
     * @param array            $documentManager A configured ORM document manager.
     * @param ContainerBuilder $container     A ContainerBuilder instance
     */
    protected function loadOdmCacheDrivers(array $documentManager, ContainerBuilder $container)
    {
        $this->loadOdmDocumentManagerCacheDriver($documentManager, $container, 'metadata_cache');
    }

    /**
     * Loads a configured document managers metadata, query or result cache driver.
     *
     * @param array            $documentManager A configured ORM document manager.
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param string           $cacheName
     */
    protected function loadOdmDocumentManagerCacheDriver(array $documentManager, ContainerBuilder $container, $cacheName)
    {
        $cacheDriverService = sprintf('doctrine_couchdb.odm.%s_%s', $documentManager['name'], $cacheName);

        $driver = $cacheName."_driver";
        $cacheDef = $this->getDocumentManagerCacheDefinition($documentManager, $documentManager[$driver], $container);
        $container->setDefinition($cacheDriverService, $cacheDef);
    }

    /**
     * Gets an document manager cache driver definition for caches.
     *
     * @param array            $documentManager The array configuring an document manager.
     * @param array            $cacheDriver The cache driver configuration.
     * @param ContainerBuilder $container
     * @return Definition $cacheDef
     */
    protected function getDocumentManagerCacheDefinition(array $documentManager, $cacheDriver, ContainerBuilder $container)
    {
        switch ($cacheDriver['type']) {
            case 'memcache':
                $memcacheClass = !empty($cacheDriver['class']) ? $cacheDriver['class'] : '%doctrine_couchdb.odm.cache.memcache.class%';
                $memcacheInstanceClass = !empty($cacheDriver['instance_class']) ? $cacheDriver['instance_class'] : '%doctrine_couchdb.odm.cache.memcache_instance.class%';
                $memcacheHost = !empty($cacheDriver['host']) ? $cacheDriver['host'] : '%doctrine_couchdb.odm.cache.memcache_host%';
                $memcachePort = !empty($cacheDriver['port']) ? $cacheDriver['port'] : '%doctrine_couchdb.odm.cache.memcache_port%';
                $cacheDef = new Definition($memcacheClass);
                $memcacheInstance = new Definition($memcacheInstanceClass);
                $memcacheInstance->addMethodCall('connect', array(
                    $memcacheHost, $memcachePort
                ));
                $container->setDefinition(sprintf('doctrine_couchdb.odm.%s_memcache_instance', $documentManager['name']), $memcacheInstance);
                $cacheDef->addMethodCall('setMemcache', array(new Reference(sprintf('doctrine_couchdb.odm.%s_memcache_instance', $documentManager['name']))));
                break;
            case 'apc':
            case 'array':
            case 'xcache':
                $cacheDef = new Definition('%'.sprintf('doctrine_couchdb.odm.cache.%s.class', $cacheDriver['type']).'%');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('"%s" is an unrecognized Doctrine cache driver.', $cacheDriver['type']));
        }

        $cacheDef->setPublic(false);
        // generate a unique namespace for the given application
        $namespace = 'sf2couchdb_'.$documentManager['name'].'_'.md5($container->getParameter('kernel.root_dir'));
        $cacheDef->addMethodCall('setNamespace', array($namespace));

        return $cacheDef;
    }

    protected function getObjectManagerElementName($name)
    {
        return 'doctrine_couchdb.odm.'.$name;
    }

    protected function getMappingObjectDefaultName()
    {
        return 'CouchDocument';
    }

    protected function getMappingResourceConfigDirectory()
    {
        return 'Resources/config/doctrine';
    }

    protected function getMappingResourceExtension()
    {
        return 'couchdb';
    }
}
