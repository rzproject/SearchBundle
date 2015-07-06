<?php
namespace Rz\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class SolrIndexCommand extends ContainerAwareCommand
{

    protected function configure() {
        $this->setName('rz:solr:index')
             ->setDescription('Index doctrine entity using Apache Solr.')
             ->addOption('entity',null, InputOption::VALUE_REQUIRED, 'Entity including full path/namespace. Eg. Application/Sonata/NewsBundle/Entity/Post');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getOption('entity');
        $info_style = new OutputFormatterStyle('blue', null, array('bold'));
        $output->getFormatter()->setStyle('rz-msg', $info_style);

        $error_style = new OutputFormatterStyle('red', null, array('bold'));
        $output->getFormatter()->setStyle('rz-err', $error_style);

        if ($entity) {
            $output->writeln(sprintf('<info>Indexing entity: <rz-msg>%s</rz-msg></info>', $entity));
            $entity_id = preg_replace('/\//', '.', strtolower($entity));

            $configManager = $this->getContainer()->get('rz_search.config_manager');
            $modelManager = $this->getContainer()->get($configManager->getModelManager($entity_id));
            $searchClient = $this->getContainer()->get('solarium.client');
            $indexManager = $this->getContainer()->get('rz_search.manager.solr_index');

            if($indexManager) {
                $data = $modelManager->findAll();
                $doc = array();
                $len = count($data);
                $i = 0;
                $result = array();
                //for now pager is hard coded
                $batch_count = 0;
                $progress = $this->getHelperSet()->get('progress');

                try {
                    $indexObject = $searchClient->createUpdate();
                    //TODO add pager for bulk index
                    $progress->start($output, $i);
                    foreach($data as $model) {
                        if ($configManager->hasConfig($entity_id)) {
                            try {
                                $doc[$batch_count] = $indexManager->indexData('insert', $indexObject, $model, $entity_id);
                                // commit every after batch count
                                if ($batch_count >= 10 || ($i == $len - 1)) {
                                    // add the documents and a commit command to the update query
                                    $indexObject->addDocuments($doc);
                                    $indexObject->setOmitHeader(true);
                                    $indexObject->addCommit();
                                    // this executes the query and returns the result
                                    $result[] = $searchClient->update($indexObject);
                                    if($batch_count >= 10) {
                                        $batch_count = 0;
                                        $doc = array();
                                    }
                                }
                                $batch_count++;
                            } catch (\Exception $e) {
                                throw $e;
                            }
                        }
                        $i++;
                        $progress->advance();
                        sleep(.25);
                    }
                } catch(\Exception $e) {
                    throw $e;
                }

                $progress->finish();

            }
            $output->writeln(sprintf('<info>Finish indexing: <rz-msg>%s</rz-msg></info>', $entity));
        } else {
            $output->writeln('<rz-err>Option entity required!</rz-err>');
        }
    }
}
