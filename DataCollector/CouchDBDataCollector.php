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

namespace Doctrine\Bundle\CouchDBBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\CouchDB\HTTP\LoggingClient;

class CouchDBDataCollector extends DataCollector
{
    private $clients = array();
    
    public function addLoggingClient(LoggingClient $client, $name)
    {
        $this->clients[$name] = $client;
    }
    
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array('duration' => array(), 'requests' => array(), 'requestcount' => 0, 'total_duration' => 0);
        foreach ($this->clients AS $name => $client) {
            $this->data['duration'][$name] = $client->totalDuration;
            $this->data['requests'][$name] = $client->requests;
            $this->data['requestcount'] += count($client->requests);
            $this->data['total_duration'] += $client->totalDuration;
        }
    }
    
    public function getDuration()
    {
        return $this->data['duration'];
    }
    
    public function getRequests()
    {
        return $this->data['requests'];
    }
    
    public function getRequestCount()
    {
        return $this->data['requestcount'];
    }
    
    public function getTotalDuration()
    {
        return $this->data['total_duration'];
    }
    
    public function getName()
    {
        return 'couchdb';
    }
}