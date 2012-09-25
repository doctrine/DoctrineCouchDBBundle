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

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\DoctrineType;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;

class DocumentType extends DoctrineType
{
    /**
     * CouchDB does not support the queryBuilder, will throw a FormException upon invocation
     *
     * @param ObjectManager $manager
     * @param mixed         $queryBuilder
     * @param string        $class
     *
     * @return EntityLoaderInterface
     *
     * @throws FormException
     */
    public function getLoader(ObjectManager $manager, $queryBuilder, $class)
    {
        throw new FormException('The query builder option is not supported by CouchDB.');
    }

    public function getName()
    {
        return 'couchdb_document';
    }
}
