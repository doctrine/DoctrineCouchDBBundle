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

use Symfony\Component\Form\Exception\FormException;

class CouchDBDocumentChoiceList extends PersistentObjectChoiceList
{
    protected function doGetPersistentObject($key)
    {
        // CouchDB has no composite keys.
        if ($this->persistentObjects) {
            return isset($this->persistentObjects[$key]) ? $this->persistentObjects[$key] : null;
        }

        return $this->objectManager->find($this->class, $key);
    }

    public function getIdentifierValues($object)
    {
        if (!$this->objectManager->getUnitOfWork()->isInIdentityMap($object)) {
            throw new FormException('Documents passed to the choice field must be managed in the Document Manager.');
        }

        // has to be array of
        return array($this->objectManager->getUnitOfWork()->getDocumentIdentifier($object));
    }
}