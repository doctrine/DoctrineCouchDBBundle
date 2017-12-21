<?php


namespace Doctrine\Bundle\CouchDBBundle\Tests;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Doctrine\\Common\\Version')) {
            $this->markTestSkipped('Doctrine Common is not available.');
        }
        if (!class_exists('Doctrine\\CouchDB\\Version')) {
            $this->markTestSkipped('Doctrine CouchDB is not available.');
        }
    }
}
