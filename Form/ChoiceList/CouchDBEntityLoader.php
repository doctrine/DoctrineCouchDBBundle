<?php

namespace Doctrine\Bundle\CouchDBBundle\Form\ChoiceList;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\CouchDB\DocumentRepository;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @author Daniel Espendiller <daniel@espendiller.net>
 */
class CouchDBEntityLoader implements EntityLoaderInterface
{

    /**
     * @var string
     */
    private $class;

    /**
     * @var ObjectManager
     */
    private $dm;

    public function __construct(ObjectManager $dm, $class)
    {
        $this->dm = $dm;
        $this->class = $class;
    }


    /**
     * {@inheritDoc}
     */
    public function getEntities()
    {
        return $this->dm->getRepository($this->class)->findAll();
    }

    /**
     * {@inheritDoc}
     */
    public function getEntitiesByIds($identifier, array $values)
    {
        $or = $this->dm->getRepository($this->class);
        if (!($or instanceof DocumentRepository)) {
            throw new UnexpectedTypeException($this->dm, 'Doctrine\ODM\CouchDB\DocumentRepository');
        }

        return $or->findMany($values);
    }
}