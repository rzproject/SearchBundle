<?php
namespace Rz\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Rz\SearchBundle\Model\ConfigManagerInterface;

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
            $update = $searchClient->createUpdate();
            $data = $modelManager->findAll();

            $doc = array();
            $len = count($data);
            $i = 0;
            $result = array();
            //TODO add pager for bulk index
            //for now pager is hard coded
            $batch_count = 0;

            $progress = $this->getHelperSet()->get('progress');
            $progress->start($output, $i);

            foreach($data as $model) {
                if ($configManager->hasConfig($entity_id)) {
                    try {
                        $doc[$batch_count] = $this->indexData($configManager, $update, $model, $entity_id);
                        // commit every after batch count
                        if ($batch_count >= 10 || ($i == $len - 1)) {
                            // add the documents and a commit command to the update query
                            $update->addDocuments($doc);
                            $update->addCommit();
                            // this executes the query and returns the result
                            $result[] = $searchClient->update($update);
                            if($batch_count >= 10) {
                                $batch_count = 0;
                            }
                        }
                        $batch_count++;
                    } catch (\Exception $e) {
                        var_dump($e);
                        die();
                    }
                }
                $i++;
                $progress->advance();
                sleep(.25);
            }
            $progress->finish();
            $output->writeln(sprintf('<info>Finish indexing: <rz-msg>%s</rz-msg></info>', $entity));
        } else {
            $output->writeln('<rz-err>Option entity required!</rz-err>');
        }
    }

    protected function indexData($configManager, $update, $entity, $entity_id)
    {

        // create a new document for the data
        $doc = $update->createDocument();
        $doc->setField('id', $configManager->getModelIdentifier($entity_id).'_'.$entity->getId());
        $doc->setField('model_id', $entity->getId());
        $doc->setField('index_type', $entity_id);

        $indexFields = $configManager->getIndexFields($entity_id);

        foreach ($indexFields as $field) {
            $value = null;
            $value = $configManager->getFieldValue($entity_id, $entity, $field);
            try {
                if (is_array($value)) {
                    foreach($value as $val) {
                        $doc->addField($field, $val);
                    }
                } else {
                    $doc->setField($field, $value);
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $doc;
    }
}
