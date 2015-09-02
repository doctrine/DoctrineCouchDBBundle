<?php

namespace Doctrine\Bundle\CouchDBBundle\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Command\Command,
    Doctrine\ODM\CouchDB\Tools\Console\Command\UpdateDesignDocCommand AS DoctrineUpdateDesignDocCommand;

class UpdateDesignDocCommand extends DoctrineUpdateDesignDocCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:couchdb:update-design-doc')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command', 'default');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        DoctrineCommandHelper::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm') ?: 'default');

        return parent::execute($input, $output);
    }
}
