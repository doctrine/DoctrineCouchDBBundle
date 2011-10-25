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

interface PersistenceRegistryInterface
{
    /**
     * Gets the default connection name.
     *
     * @return string The default connection name
     */
    function getDefaultConnectionName();

    /**
     * Gets the named connection.
     *
     * @param string $name The connection name (null for the default one)
     *
     * @return Connection
     */
    function getConnection($name = null);

    /**
     * Gets an array of all registered connections
     *
     * @return array An array of Connection instances
     */
    function getConnections();

    /**
     * Gets all connection names.
     *
     * @return array An array of connection names
     */
    function getConnectionNames();

    /**
     * Gets the default object manager name.
     *
     * @return string The default object manager name
     */
    function getDefaultObjectManagerName();

    /**
     * Gets a named object manager.
     *
     * @param string $name The object manager name (null for the default one)
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    function getObjectManager($name = null);

    /**
     * Gets an array of all registered object managers
     *
     * @return array An array of ObjectManager instances
     */
    function getObjectManagers();

    /**
     * Resets a named object manager.
     *
     * This method is useful when an object manager has been closed
     * because of a rollbacked transaction AND when you think that
     * it makes sense to get a new one to replace the closed one.
     *
     * Be warned that you will get a brand new object manager as
     * the existing one is not useable anymore. This means that any
     * other object with a dependency on this object manager will
     * hold an obsolete reference. You can inject the registry instead
     * to avoid this problem.
     *
     * @param string $name The object manager name (null for the default one)
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    function resetObjectManager($name = null);

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * This method looks for the alias in all registered object managers.
     *
     * @param string $alias The alias
     *
     * @return string The full namespace
     */
    function getObjectNamespace($alias);

    /**
     * Gets all connection names.
     *
     * @return array An array of connection names
     */
    function getObjectManagerNames();

    /**
     * Gets the ObjectRepository for an persistent object.
     *
     * @param string $persistentObject        The name of the persistent object.
     * @param string $persistentObjectManagerName The object manager name (null for the default one)
     *
     * @return Doctrine\Common\Persistence\ObjectRepository
     */
    function getRepository($persistentObject, $persistentObjectManagerName = null);

    /**
     * Gets the object manager associated with a given class.
     *
     * @param string $class A persistent object class name
     *
     * @return ObjectManager|null
     */
    function getObjectManagerForClass($class);
}