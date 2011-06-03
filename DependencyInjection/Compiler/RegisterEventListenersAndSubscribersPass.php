<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\DoctrineCouchDBBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class RegisterEventListenersAndSubscribersPass implements CompilerPassInterface
{
    private $container;
    private $documentManagers;
    private $eventManagers;

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('doctrine_couchdb.default_connection')) {
            return;
        }

        $this->container = $container;
        $this->documentManagers = $container->getParameter('doctrine_couchdb.document_managers');

        foreach ($container->findTaggedServiceIds('doctrine_couchdb.event_subscriber') as $subscriberId => $instances) {
            $this->registerSubscriber($subscriberId, $instances);
        }

        foreach ($container->findTaggedServiceIds('doctrine_couchdb.event_listener') as $listenerId => $instances) {
            $this->registerListener($listenerId, $instances);
        }
    }

    protected function registerSubscriber($subscriberId, $instances)
    {
        $connections = array();
        foreach ($instances as $attributes) {
            if (isset($attributes['document_manager'])) {
                $connections[] = $attributes['document_manager'];
            } else {
                $connections = array_keys($this->documentManagers);
                break;
            }
        }

        foreach ($connections as $name) {
            $this->getEventManager($name, $subscriberId)->addMethodCall('addEventSubscriber', array(new Reference($subscriberId)));
        }
    }

    protected function registerListener($listenerId, $instances)
    {
        $connections = array();
        foreach ($instances as $attributes) {
            if (!isset($attributes['event'])) {
                throw new \InvalidArgumentException(sprintf('Doctrine event listener "%s" must specify the "event" attribute.', $listenerId));
            }

            if (isset($attributes['document_manager'])) {
                $cs = array($attributes['document_manager']);
            } else {
                $cs = array_keys($this->documentManagers);
            }

            foreach ($cs as $connection) {
                if (!isset($connections[$connection]) || !is_array($connections[$connection])) {
                    $connections[$connection] = array();
                }
                $connections[$connection][] = $attributes['event'];
            }
        }

        foreach ($connections as $name => $events) {
            $this->getEventManager($name, $listenerId)->addMethodCall('addEventListener', array(
                array_unique($events),
                new Reference($listenerId),
            ));
        }
    }

    private function getEventManager($name, $listenerId = null)
    {
        if (null === $this->eventManagers) {
            $this->eventManagers = array();
            foreach ($this->documentManagers as $n => $id) {
                $arguments = $this->container->getDefinition($id)->getArguments();
                $this->eventManagers[$n] = $this->container->getDefinition((string) $arguments[2]);
            }
        }

        if (!isset($this->eventManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Doctrine connection "%s" does not exist but is referenced in the "%s" event listener.', $name, $listenerId));
        }

        return $this->eventManagers[$name];
    }
}
