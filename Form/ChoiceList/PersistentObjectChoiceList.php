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

namespace Doctrine\Bundle\CouchDBBundle\Form\ChoiceList;

use Symfony\Component\Form\Util\PropertyPath;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Abstract choice list to be used with PersistentObject storages.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class PersistentObjectChoiceList extends ArrayChoiceList
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $objectManager;

    /**
     * Class name of the persistent object
     *
     * @var string
     */
    protected $class;

    /**
     * The persistent objects from which the user can choose
     *
     * This array is either indexed by ID (if the ID is a single field)
     * or by key in the choices array (if the ID consists of multiple fields)
     *
     * This property is initialized by initializeChoices(). It should only
     * be accessed through getPersistentObject() and getPersistentObjects().
     *
     * @var Collection
     */
    protected $persistentObjects = array();


    /**
     * The fields of which the identifier of the underlying class consists
     *
     * This property should only be accessed through identifier.
     *
     * @var array
     */
    protected $identifier = array();

    /**
     * @var PropertyPath
     */
    private $propertyPath;

    /**
     * @param ObjectManager $objectManager
     * @param string $class
     * @param string|null $property
     * @param array $choices
     */
    public function __construct(ObjectManager $objectManager, $class, $property = null, $choices = array())
    {
        $this->objectManager = $objectManager;
        $this->class = $class;
        $this->identifier = $objectManager->getClassMetadata($class)->getIdentifier();

        // The property option defines, which property (path) is used for
        // displaying entities as strings
        if ($property) {
            $this->propertyPath = new PropertyPath($property);
        }

        parent::__construct($choices);
    }

    /**
     * Initializes the choices and returns them
     *
     * If the entities were passed in the "choices" option, this method
     * does not have any significant overhead. Otherwise, if a query builder
     * was passed in the "query_builder" option, this builder is now used
     * to construct a query which is executed. In the last case, all entities
     * for the underlying class are fetched from the repository.
     *
     * @return array  An array of choices
     */
    protected function load()
    {
        parent::load();

        $objects = $this->getChoiceObjects();

        $this->choices = array();
        $this->persistentObjects = array();

        $this->loadPersistentObjects($objects);

        return $this->choices;
    }

    protected function getChoiceObjects()
    {
        if ($this->choices) {
            $objects = $this->choices;
        } else {
            $objects = $this->objectManager->getRepository($this->class)->findAll();
        }
        return $objects;
    }

    /**
     * Convert persistent objects into choices with support for groups
     *
     * The choices are generated from the objects. If the objects have a
     * composite identifier, the choices are indexed using ascending integers.
     * Otherwise the identifiers are used as indices.
     *
     * If the option "property" was passed, the property path in that option
     * is used as option values. Otherwise this method tries to convert
     * objects to strings using __toString().
     *
     */
    private function loadPersistentObjects($objects, $group = null)
    {
        $isComposite = (count($this->identifier) > 1);
        foreach ($objects as $key => $object) {
            if (is_array($object)) {
                // Entities are in named groups
                $this->loadPersistentObjects($object, $key);
                continue;
            }

            if ($this->propertyPath) {
                // If the property option was given, use it
                $value = $this->propertyPath->getValue($object);
            } else {
                // Otherwise expect a __toString() method in the entity
                if (!method_exists($object, '__toString')) {
                    throw new FormException('Persistent objects (Entities, Documents) passed to the choice field must have a "__toString()" method defined (or you can also override the "property" option).');
                }

                $value = (string)$object;
            }

            if ($isComposite) {
                // When the identifier consists of multiple field, use
                // naturally ordered keys to refer to the choices
                $id = $key;
            } else {
                // When the identifier is a single field, index choices by
                // entity ID for performance reasons
                $id = current($this->getIdentifierValues($object));
            }

            if (null === $group) {
                // Flat list of choices
                $this->choices[$id] = $value;
            } else {
                // Nested choices
                $this->choices[$group][$id] = $value;
            }

            $this->persistentObjects[$id] = $object;
        }
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the according entities for the choices
     *
     * If the choices were not initialized, they are initialized now. This
     * is an expensive operation, except if the entities were passed in the
     * "choices" option.
     *
     * @return array  An array of entities
     */
    public function getPersistentObjects()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->persistentObjects;
    }

    /**
     * Returns the entity for the given key
     *
     * If the underlying entities have composite identifiers, the choices
     * are initialized. The key is expected to be the index in the choices
     * array in this case.
     *
     * If they have single identifiers, they are either fetched from the
     * internal entity cache (if filled) or loaded from the database.
     *
     * @param  string $key  The choice key (for entities with composite
     *                      identifiers) or entity ID (for entities with single
     *                      identifiers)
     * @return object       The matching entity
     */
    public function getPersistentObject($key)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->doGetPersistentObject($key);
    }

    abstract protected function doGetPersistentObject($key);

    /**
     * Returns the values of the identifier fields of an object
     *
     * Doctrine must know about this entity, that is, the object must already
     * be persisted or added to the identity map before. Otherwise an
     * exception is thrown.
     *
     * @param  object $object  The object for which to get the identifier
     * @throws FormException   If the object does not exist in Doctrine's
     *                         identity map
     */
    abstract public function getIdentifierValues($object);
}
