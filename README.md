# Doctrine CouchDB Bundle

This bundle integrates Doctrine CouchDB ODM and Clients into Symfony2.

## Installation

* Grab this repository and [Doctrine CouchDB ODM](http://github.com/doctrine/couchdb-odm) into your Symfony project
* Add `Doctrine\Bundle\CouchDBBundle\DoctrineCouchDBBundle` to your Kernel#registerBundles() method
* Add autoloader for Doctrine\CouchDB, Doctrine\ODM\CouchDB and Doctrine\Bundle namespaces

## Configuration

The configuration is similar to Doctrine ORM and MongoDB configuration for Symfony2 as its based
on the AbstractDoctrineBundle aswell:

    doctrine_couch_db:
      client:
        dbname: symfony
      odm:
        auto_mapping: true

## Services

You can access to CouchDB services:

    <?php

    namespace Acme\DemoBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    class DefaultController extends Controller
    {
        public function indexAction()
        {
            $client = $this->container->get('doctrine_couchdb.client.default_connection');
            $documentManager = $this->container->get('doctrine_couchdb.odm.default_document_manager');
        }
    }

