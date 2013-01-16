# Doctrine CouchDB Bundle

This bundle integrates Doctrine CouchDB ODM and Clients into Symfony2.

STABILITY: Alpha

## Installation

* Grab this repository and [Doctrine CouchDB ODM](http://github.com/doctrine/couchdb-odm) into your Symfony project
* Add `Doctrine\Bundle\CouchDBBundle\DoctrineCouchDBBundle` to your Kernel#registerBundles() method
* Add autoloader for Doctrine\CouchDB, Doctrine\ODM\CouchDB and Doctrine\Bundle namespaces

## Documentation

See the [Doctrine CouchDB ODM](http://www.doctrine-project.org/docs/couchdb_odm/1.0/en/) documentation for more information.

## Configuration

The configuration is similar to Doctrine ORM and MongoDB configuration for Symfony2 as its based
on the AbstractDoctrineBundle aswell:

    doctrine_couch_db:
      client:
        dbname: symfony
      odm:
        auto_mapping: true

or call

    php app/console config:dump-reference doctrine_couch_db

## Annotations

An example of how to use annotations with CouchDB and Symfony:

    <?php
    namespace Acme\DemoBundle\CouchDocument;

    use Doctrine\ODM\CouchDB\Mapping\Annotations as CouchDB;

    /**
     * @CouchDB\Document
     */
    class User
    {
        /** @CouchDB\Id */
        private $id;
    }

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

## View directories

In `@YourBundle/Resources/couchdb/` you can add design documents and corresponding views and have Doctrine
CouchDB register them up automatically. For example if you had a design doc "foo" and a view "bar" you could
add the following files and directories:

    Resources/couchdb/
    └── foo/
        └── views/
            └── bar/
                ├── map.js
                └── reduce.js

You can then update this design document from the CLI by calling:

    ./app/console doctrine:couchdb:update-design-doc foo

Where `foo` is the name of the design document.
