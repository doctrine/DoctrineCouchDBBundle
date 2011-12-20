<?php

/*
 * This file is part of the Doctrine CouchDBBundle
 *
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\CouchDBBundle\Validator\Constraints;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as BaseConstraint;

/**
 * Constraint for the Unique Entity validator
 *
 * @Annotation
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class UniqueEntity extends BaseConstraint
{
    public $service = 'doctrine_couchdb.odm.validator.unique';
}
