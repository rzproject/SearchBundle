<?php
namespace Rz\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Rz\SearchBundle\Model\ConfigManagerInterface;

class LuceneIndexCommand extends ContainerAwareCommand
{

    protected function configure() {
        $this->setName('rz:lucene:index')
            ->setDescription('Index doctrine entity using Zend Lucene.')
            ->addOption('identifier',null, InputOption::VALUE_REQUIRED, 'Identifier as defined on rz_search.yml.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $identifier = $input->getOption('identifier');
        $info_style = new OutputFormatterStyle('blue', null, array('bold'));
        $output->getFormatter()->setStyle('rz-msg', $info_style);

        $error_style = new OutputFormatterStyle('red', null, array('bold'));
        $output->getFormatter()->setStyle('rz-err', $error_style);

        if ($identifier) {
            $output->writeln(sprintf('<info>Indexing entity: <rz-msg>%s</rz-msg></info>', $identifier));
            $configManager = $this->getContainer()->get('rz_search.config_manager');
            $modelManager = null;
            if ($configManager->hasConfig($identifier)) {
                $modelManagerId = $configManager->getModelManager($identifier);
                if($modelManagerId) {
                    $modelManager = $this->getContainer()->get($modelManagerId);
                    $filters = $configManager->getModelIndexFilter($identifier);

                    if($modelManager) {
                        $askTheExperts = $modelManager->findAll();
                        // Initialize Variables
                        $index = $this->getContainer()->get('rz_search.zend_lucene')->getIndex($identifier);
                        $progress = $this->getHelperSet()->get('progress');
                        $doc = array();
                        $len = count($askTheExperts);
                        $i = 0;
                        $result = array();
                        //TODO add pager for bulk index
                        //for now pager is hard coded
                        $batch_count = 0;

                        $progress->start($output, $i);

                        foreach($askTheExperts as $entity) {
                            $val = null;
                            if ($filters) {
                                foreach($filters as $fieldName=>$filter) {
                                    $getter = 'get'.ucfirst($fieldName);
                                    switch ($filter['operand']) {
                                        case '=':
                                            $val = ($entity->$getter() == $filter['value']) ? true : false;
                                            break;
                                        case '!=':
                                            $val = ($entity->$getter() != $filter['value']) ? true : false;
                                            break;
                                    }
                                }

                                if($val) {
                                    try {
                                        $doc[$batch_count] = $this->indexDataZendLucene('update', $entity, $identifier, $index, $configManager);
                                    } catch (\Exception $e) {
                                        throw $e;
                                    }
                                }
                            } else {
                                try {
                                    $doc[$batch_count] = $this->indexDataZendLucene('update', $entity, $identifier, $index, $configManager);
                                } catch (\Exception $e) {
                                    throw $e;
                                }
                            }

                            if ($batch_count >= 10 || ($i == $len - 1)) {
                                // add the documents and a commit command to the update query
                                $index->addDocuments($doc);
                                $index->addCommit();
                                $index->optimize();
                                if($batch_count >= 10) {
                                    $batch_count = 0;
                                }
                            }
                            $batch_count++;
                            $i++;
                            $progress->advance();
                            sleep(.25);
                        }

                        $progress->finish();
                        $output->writeln(sprintf('<info>Finish indexing: <rz-msg>%s</rz-msg></info>', $identifier));

                    } else {
                        $output->writeln(sprintf('<rz-err>Model Manager service %s does not exist!</rz-err>', $modelManagerId));
                        exit;
                    }
                } else {
                    $output->writeln(sprintf('<rz-err>Model Manager for Identifier %s does not exist!</rz-err>', $identifier));
                    exit;
                }
            } else {
                $output->writeln(sprintf('<rz-err>Identifier %s does not exist!</rz-err>', $identifier));
                exit;
            }

        } else {
            $output->writeln('<rz-err>Identifier required!</rz-err>');
        }
    }

    protected function indexDataZendLucene($type, $entity, $entity_id, $index, $configManager)
    {
        $id = $configManager->getModelIdentifier($entity_id).'_'.$entity->getId();

        if ($type == 'update') {
            $term = new Term($id, 'uuid');
            $docIds = $index->termDocs($term);
            if($docIds) {
                foreach ($docIds as $docId) {
                    $index->delete($docId);
                }
            }
        }

        // Create a new document
        $doc = new Document();

        $doc->addField(Field::keyword('uuid', $id));
        $doc->addField(Field::keyword('model_id', $entity->getId()));
        $doc->addField(Field::keyword('index_type', $entity_id));

        if($route = $this->configManager->getFieldRouteGenerator($entity_id)) {
            $routeGenerator = $this->getContainer()->get($route);
            if($routeGenerator) {
                $doc->addField(Field::unIndexed('url', $routeGenerator->generate($entity)));
            }
        }

        $indexFields = $this->configManager->getIndexFields($entity_id);

        $searchContent = null;
        foreach ($indexFields as $field) {
            $value = null;
            $settings = $this->configManager->getIndexFieldSettings($entity_id, $field);

            $config = isset($settings['fields']) ? $settings['fields'] : null;
            $value = $this->configManager->getFieldValue($entity_id, $entity, $field, $config);

            try {
                if (is_array($value)) {
                    foreach($value as $val) {
                        $doc->addField(Field::$settings['type']($field, $val));
                        $searchContent .= $val;
                    }
                } else {
                    $doc->addField(Field::$settings['type']($field, $value));
                    $searchContent .= $value;
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
        //default search field
        $doc->addField(Field::unStored('searchContent', $searchContent));
        return $doc;
    }
}
