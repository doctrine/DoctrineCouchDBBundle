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

namespace Doctrine\Bundle\CouchDBBundle;

use Symfony\Bridge\Doctrine\ManagerRegistry as BaseManagerRegistry;
use Doctrine\ODM\CouchDB\CouchDBException;

class ManagerRegistry extends BaseManagerRegistry
{
    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @param string $alias
     * @return string
     * @throws CouchDBException
     */
    public function getAliasNamespace($alias)
    {
        foreach (array_keys($this->getManagers()) as $name) {
            try {
                return $this->getManager($name)->getConfiguration()->getDocumentNamespace($alias);
            } catch (CouchDBException $e) {
            }
        }

        throw CouchDBException::unknownDocumentNamespace($alias);
    }
}
