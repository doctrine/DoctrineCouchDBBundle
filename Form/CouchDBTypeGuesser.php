<?php


namespace Doctrine\Bundle\CouchDBBundle\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * Guesser for Form component using Doctrine CouchDB registry and metadata.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class CouchDBTypeGuesser implements FormTypeGuesserInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    private $cache = array();

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function guessType($class, $property)
    {
        if (!$ret = $this->getMetadata($class)) {
            return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }

        list($metadata, $documentManager) = $ret;

        if ($metadata->hasAssociation($property)) {
            $multiple = $metadata->isCollectionValuedAssociation($property);
            $mapping = $metadata->getAssociationMapping($property);

            return new TypeGuess('couchdb_document', array(
                'dm' => $documentManager,
                'class' => $mapping['targetDocument'],
                'multiple' => $multiple
                ),
                Guess::HIGH_CONFIDENCE
            );
        }

        switch ($metadata->getTypeOfField($property)) {
            case 'boolean':
                return new TypeGuess('checkbox', array(), Guess::HIGH_CONFIDENCE);
            case 'datetime':
                return new TypeGuess('datetime', array(), Guess::HIGH_CONFIDENCE);
            case 'integer':
                return new TypeGuess('integer', array(), Guess::MEDIUM_CONFIDENCE);
            case 'string':
                return new TypeGuess('text', array(), Guess::MEDIUM_CONFIDENCE);
            default:
                return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function guessMaxLength($class, $property)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function guessMinLength($class, $property)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function guessRequired($class, $property)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function guessPattern($class, $property)
    {
    }

    protected function getMetadata($class)
    {
        if (array_key_exists($class, $this->cache)) {
            return $this->cache[$class];
        }

        $manager = $this->registry->getManagerForClass($class);
        if ($manager) {
            return $this->cache[$class] = array($manager->getClassMetadata($class), $manager);
        }
        return $this->cache[$class] = null;
    }
}