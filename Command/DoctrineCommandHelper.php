<?php

namespace Doctrine\Bundle\CouchDBBundle\Command;

use Symfony\Component\Console\Application;
use Doctrine\CouchDB\Tools\Console\Helper\CouchDBHelper;

/**
 * Provides some helper and convenience methods to configure doctrine commands in the context of bundles
 * and multiple connections/entity managers.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
abstract class DoctrineCommandHelper
{
    static public function setApplicationCouchDBClient(Application $application, $connName)
    {
        $couchClient = $application->getKernel()->getContainer()->get('doctrine_couchdb.client.'.$connName.'_connection');
        $helperSet = $application->getHelperSet();
        $helperSet->set(new CouchDBHelper($couchClient));
    }
    
    static public function setApplicationDocumentManager(Application $application, $dmName)
    {
        $documentManager = $application->getKernel()->getContainer()->get('doctrine_couchdb.odm.'.$dmName.'_document_manager');
        $helperSet = $application->getHelperSet();
        $helperSet->set(new CouchDBHelper(null, $documentManager));
    }
}