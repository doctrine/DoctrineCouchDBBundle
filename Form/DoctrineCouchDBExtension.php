<?php


namespace Doctrine\Bundle\CouchDBBundle\Form;

use Symfony\Component\Form\AbstractExtension;
use Doctrine\Common\Persistence\ManagerRegistry;

class DoctrineCouchDBExtension extends AbstractExtension
{
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    protected function loadTypes()
    {
        return array(
            new Type\DocumentType($this->registry),
        );
    }

    protected function loadTypeGuesser()
    {
        return new CouchDBTypeGuesser($this->registry);
    }
}
