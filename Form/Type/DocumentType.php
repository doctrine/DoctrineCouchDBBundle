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

namespace Doctrine\Bundle\CouchDBBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\AbstractType;
use Doctrine\Bundle\CouchDBBundle\Form\ChoiceList\CouchDBDocumentChoiceList;

class DocumentType extends PersistentObjectType
{
    protected function getObjectManagerKey()
    {
        return 'dm';
    }

    /**
     * @return PersistentObjectChoiceList
     */
    protected function createDefaultChoiceList($options)
    {
        $documentManager = is_object($options['dm']) ?: $this->registry->getManager($options['dm']);
        return new CouchDBDocumentChoiceList(
            $documentManager,
            $options['class'],
            $options['property'],
            $options['choices']
        );
    }

    public function getName()
    {
        return 'doctrine_couchdb_document';
    }
}
