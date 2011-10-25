<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\CouchDBBundle\Form\DataTransformer;

use Doctrine\Bundle\CouchDBBundle\Form\ChoiceList\PersistentObjectChoiceList;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

class PersistentObjectToArrayTransformer implements DataTransformerInterface
{
    private $choiceList;

    public function __construct(PersistentObjectChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * Transforms persistent objects into choice keys
     *
     * @param Collection|object $collection A collection of persistents objects, a single PO or NULL
     * @return mixed An array of choice keys, a single key or NULL
     */
    public function transform($collection)
    {
        if (null === $collection) {
            return array();
        }

        if (!($collection instanceof Collection)) {
            throw new UnexpectedTypeException($collection, 'Doctrine\Common\Collections\Collection');
        }

        $array = array();

        if (count($this->choiceList->getIdentifier()) > 1) {
            // load all choices
            $availableEntities = $this->choiceList->getPersistentObjects();

            foreach ($collection as $entity) {
                // identify choices by their collection key
                $key = array_search($entity, $availableEntities);
                $array[] = $key;
            }
        } else {
            foreach ($collection as $entity) {
                $array[] = current($this->choiceList->getIdentifierValues($entity));
            }
        }

        return $array;
    }

    /**
     * Transforms choice keys into persistent objects
     *
     * @param  mixed $keys   An array of keys, a single key or NULL
     * @return Collection|object  A collection of persistent objects, a single PO or NULL
     */
    public function reverseTransform($keys)
    {
        $collection = new ArrayCollection();

        if ('' === $keys || null === $keys) {
            return $collection;
        }

        if (!is_array($keys)) {
            throw new UnexpectedTypeException($keys, 'array');
        }

        $notFound = array();

        // optimize this into a SELECT WHERE IN query
        foreach ($keys as $key) {
            if ($object = $this->choiceList->getPersistentObject($key)) {
                $collection->add($object);
            } else {
                $notFound[] = $key;
            }
        }

        if (count($notFound) > 0) {
            throw new TransformationFailedException(
                sprintf('The persistent objects (entities or documents) with keys "%s" could not be found',
                implode('", "', $notFound))
            );
        }

        return $collection;
    }
}
