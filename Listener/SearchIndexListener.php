<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Rz\SearchBundle\Model\ConfigManagerInterface;
use Solarium\Core\Configurable as SearchClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchIndexListener
{
    protected $configManager;
    protected $searchClient;
    protected $container;

    /**
     * Constructor
     *
     * @param \Rz\SearchBundle\Model\ConfigManagerInterface $configManager
     * @param \Solarium\Core\Configurable                   $searchClient
     */
    public function __construct(ConfigManagerInterface $configManager, SearchClient $searchClient, ContainerInterface $container)
    {
        $this->configManager = $configManager;
        //TODO : add abstraction layer for client. Hard coded for now
        $this->searchClient = $searchClient;
        $this->container = $container;
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        //TODO : find a more efficient way to detect config
        $entity = $args->getEntity();
        $entity_id = preg_replace('/\\\\/', '.', strtolower(get_class($entity)));

        //$entityManager = $args->getEntityManager();
        if ($this->configManager->hasConfig($entity_id)) {
            try {
                $result = $this->indexData($entity, $entity_id);
            } catch (\Exception $e) {
              var_dump($e);
              die();
            }
        }
    }

    protected function indexData($entity, $entity_id)
    {
        $update = $this->searchClient->createUpdate();
        // create a new document for the data
        $doc = $update->createDocument();
        $doc->setField('id', $this->configManager->getModelIdentifier($entity_id).'_'.$entity->getId());
        $doc->setField('model_id', $entity->getId());
        $doc->setField('index_type', $entity_id);
        // generate route
        $routeGenerator = $this->container->get($this->configManager->getFieldRouteGenerator($entity_id));
        $doc->setField('url', $routeGenerator->generate($entity));

        $indexFields = $this->configManager->getIndexFields($entity_id);

        foreach ($indexFields as $field) {
            $value = null;
            $value = $this->configManager->getFieldValue($entity_id, $entity, $field);
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
        var_dump($doc);
        die();
        // add the documents and a commit command to the update query
        $update->addDocuments(array($doc));
        $update->addCommit();
        // this executes the query and returns the result
        return $this->searchClient->update($update);
    }

    protected function processCollectionMapping($data, $fields, $filters)
    {
        $collection = null;
        foreach ($data as $item) {
            $temp = $this->processMapping($item, $fields, $filters);
            if ($temp) {
                $collection[] = $temp;
            }
        }

        return $collection ? implode(',', $collection) : null;
    }

    protected function processMapping($item, $fields, $filters)
    {
        if (is_array($fields)) {
            $collection = null;
            foreach ($fields as $field) {
                if (is_array($filters)) {
                    if ($this->fieldIsFilterable($item, $filters)) {
                        $getter = 'get'.ucfirst($field);
                        $collection[] =  $item->$getter();
                    } else {
                        continue;
                    }
                } else {
                    $getter = 'get'.ucfirst($field);
                    $collection[] =  $item->$getter();
                }
            };

            return $collection ? implode(',', $collection) : null;
        } else {
            $getter = 'get'.ucfirst($fields);

            return $item->$getter();
        }
    }

    protected function fieldIsFilterable($item, $filters)
    {
        $is_indexed = null;
        foreach ($filters as $index =>$filter) {
            if (array_key_exists('operand', $filter) && array_key_exists('value', $filter)) {
                $val = false;
                $getter = 'get'.ucfirst($index);
                switch ($filter['operand']) {
                    case '=':
                        $val = ($item->$getter() == $filter['value']) ? true : false;
                        break;
                    case '!=':
                        $val = ($item->$getter() != $filter['value']) ? true : false;
                        break;
                }
                $is_indexed =  (null === $is_indexed) ? $val : ($is_indexed && $val);
            } else {
                continue;
            }
        }

        return $is_indexed;
    }
}
