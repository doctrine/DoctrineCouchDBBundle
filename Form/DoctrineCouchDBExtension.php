<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\CouchDBBundle\Form;

use Symfony\Component\Form\AbstractExtension;
use Doctrine\Bundle\CouchDBBundle\PersistenceRegistryInterface;

class DoctrineCouchDBExtension extends AbstractExtension
{
    protected $registry;

    public function __construct(PersistenceRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    protected function loadTypes()
    {
        return array(
            new Type\DocumentType($this->registry),
        );
    }
}
