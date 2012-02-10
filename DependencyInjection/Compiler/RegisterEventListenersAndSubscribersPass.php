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

namespace Doctrine\Bundle\CouchDBBundle\DependencyInjection\Compiler;

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
