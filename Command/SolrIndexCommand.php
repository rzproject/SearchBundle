<?php
namespace Rz\SearchBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class SolrIndexCommand extends Command
{
    protected function configure() {
        $this->setName('rz:solr:index')
             ->setDescription('Index doctrine entity using Apache Solr.')
             ->addOption('entity-id',null, InputOption::VALUE_REQUIRED, 'Entity id based on your RzSearchBundle config.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityId = $input->getOption('entity-id');
        $info_style = new OutputFormatterStyle('blue', null, array('bold'));
        $output->getFormatter()->setStyle('rz-msg', $info_style);

        $error_style = new OutputFormatterStyle('red', null, array('bold'));
        $output->getFormatter()->setStyle('rz-err', $error_style);

        if ($entityId) {
            $output->writeln(sprintf('<info>Indexing entity for configuration: <rz-msg>%s</rz-msg></info>', $entityId));
        } else {
            $output->writeln('<rz-err>entity-id required!</rz-err>');
        }

    }
}
