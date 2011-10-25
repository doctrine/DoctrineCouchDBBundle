<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Form\DataTransformer;

use Doctrine\Bundle\CouchDBBundle\Form\ChoiceList\PersistentObjectChoiceList;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PersistentObjectToIdTransformer implements DataTransformerInterface
{
    private $choiceList;

    public function __construct(PersistentObjectChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * Transforms entities into choice keys
     *
     * @param Collection|object $object A collection of persistent objects, a single PO or NULL
     * @return mixed An array of choice keys, a single key or NULL
     */
    public function transform($object)
    {
        if (null === $object || '' === $object) {
            return '';
        }

        if (!is_object($object)) {
            throw new UnexpectedTypeException($object, 'object');
        }

        if (count($this->choiceList->getIdentifier()) > 1) {
            // load all choices
            $availableObjects = $this->choiceList->getPersistentObjects();

            return array_search($object, $availableObjects);
        }

        return current($this->choiceList->getIdentifierValues($object));
    }

    /**
     * Transforms choice keys into peristent objects
     *
     * @param  mixed $key   An array of keys, a single key or NULL
     * @return Collection|object  A collection of persistent objects, a single PO or NULL
     */
    public function reverseTransform($key)
    {
        if ('' === $key || null === $key) {
            return null;
        }

        if (count($this->choiceList->getIdentifier()) > 1 && !is_numeric($key)) {
            throw new UnexpectedTypeException($key, 'numeric');
        }

        if (!($object = $this->choiceList->getPersistentObject($key))) {
            throw new TransformationFailedException(
                sprintf('The persistent object (document, entity) with key "%s" could not be found', $key
            ));
        }

        return $object;
    }
}
