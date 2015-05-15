<?php
namespace Rz\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class SolrCleanCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this->setName('rz:solr:clean')
        ->setDescription('Clean/Re-initialize Apache Solr index.')
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
            $output->writeln(sprintf('<info>Reinitializing Apache Solr core for configuration: <rz-msg>%s</rz-msg></info>', $entityId));
            $client = $this->getContainer()->get('solarium.client');
            // get an update query instance
            $update = $client->createUpdate();
            // add the delete query and a commit command to the update query
            $update->addDeleteQuery('*:*');
            $update->addCommit();
            // this executes the query and returns the result
            $result = $client->update($update);

            $output->writeln('<info>Update query executed</info>');
        } else {
            $output->writeln('<rz-err>entity-id required!</rz-err>');
        }

    }
}
