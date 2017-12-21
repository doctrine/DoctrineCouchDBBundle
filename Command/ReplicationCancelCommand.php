<?php

namespace Doctrine\Bundle\CouchDBBundle\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Command\Command,
    Doctrine\CouchDB\Tools\Console\Command\ReplicationCancelCommand AS DoctrineReplicationCancelCommand;

class ReplicationCancelCommand extends DoctrineReplicationCancelCommand
{
    protected function configure()
    {
        parent::configure();
        
        $this->setName('doctrine:couchdb:replication:cancel')
             ->addOption('conn', null, InputOption::VALUE_OPTIONAL, 'The connection to use for this command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        DoctrineCommandHelper::setApplicationCouchDBClient($this->getApplication(), $input->getOption('conn') ?: 'default');

        return parent::execute($input, $output);
    }
}
