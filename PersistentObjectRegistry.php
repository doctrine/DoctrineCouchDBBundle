<?php

/*
 * Doctrine CouchDB Bundle
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Doctrine\Bundle\CouchDBBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class PersistentObjectRegistry implements PersistenceRegistryInterface
{
    private $container;
    private $connections;
    private $objectManagers;
    private $defaultConnection;
    private $defaultObjectManager;

    public function __construct(ContainerInterface $container, array $connections, array $objectManagers, $defaultConnection, $defaultObjectManager)
    {
        $this->container = $container;
        $this->connections = $connections;
        $this->objectManagers = $objectManagers;
        $this->defaultConnection = $defaultConnection;
        $this->defaultObjectManager = $defaultObjectManager;
    }

    /**
     * @inheritdoc
     */
    public function getConnection($name = null)
    {
        if (null === $name) {
            $name = $this->defaultConnection;
        }

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf('Doctrine %s Connection named "%s" does not exist.', $this->getName(), $name));
        }

        return $this->container->get($this->connections[$name]);
    }

    /**
     * @inheritdoc
     */
    public function getConnectionNames()
    {
        return $this->connections;
    }

    /**
     * @inheritdoc
     */
    public function getConnections()
    {
        $connections = array();
        foreach ($this->connections as $name => $id) {
            $connections[$name] = $this->container->get($id);
        }

        return $connections;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultConnectionName()
    {
        return $this->defaultConnection;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultObjectManagerName()
    {
        return $this->defaultObjectManager;
    }

    /**
     * @inheritdoc
     */
    public function getObjectManager($name = null)
    {
        if (null === $name) {
            $name = $this->defaultObjectManager;
        }

        if (!isset($this->objectManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Doctrine %s Object Manager named "%s" does not exist.', $this->getName(), $name));
        }

        return $this->container->get($this->objectManagers[$name]);
    }

    /**
     * @inheritdoc
     */
    public function getObjectManagerForClass($class)
    {
        $proxyClass = new \ReflectionClass($class);
        if ($proxyClass->implementsInterface('Doctrine\ODM\CouchDB\Proxy\Proxy')) {
            $class = $proxyClass->getParentClass()->getName();
        }

        foreach ($this->objectManagers as $id) {
            $objectManager = $this->container->get($id);

            if (!$objectManager->getConfiguration()->getMetadataDriverImpl()->isTransient($class)) {
                return $objectManager;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getObjectManagerNames()
    {
        return $this->objectManagers;
    }

    /**
     * @inheritdoc
     */
    public function getObjectManagers()
    {
        $dms = array();
        foreach ($this->objectManagers as $name => $id) {
            $dms[$name] = $this->container->get($id);
        }

        return $dms;
    }

    /**
     * @inheritdoc
     */
    public function getObjectNamespace($alias)
    {
        throw new \BadMethodCallException("Not yet implemented.");
    }

    /**
     * @inheritdoc
     */
    public function getRepository($persistentObjectName, $persistentObjectManagerName = null)
    {
        return $this->getObjectManager($persistentObjectManagerName)->getRepository($persistentObjectName);
    }

    /**
     * @inheritdoc
     */
    public function resetObjectManager($name = null)
    {
        if (null === $name) {
            $name = $this->defaultObjectManager;
        }

        if (!isset($this->objectManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Doctrine %s Manager named "%s" does not exist.', $this->getName(), $name));
        }

        // force the creation of a new entity manager
        // if the current one is closed
        $this->container->set($this->objectManagers[$name], null);
    }
}