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
use Symfony\Component\DependencyInjection\ContainerInterface;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Document\Field;
use ZendSearch\Lucene\Index\Term;
use ZendSearch\Lucene\Search\Query\Term as QueryTerm;


class SearchIndexListener
{
    protected $entityId;
    protected $configManager;
    protected $searchClient;
    protected $container;

    /**
     * Constructor
     *
     * @param $id
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Rz\SearchBundle\Model\ConfigManagerInterface $configManager
     */
    public function __construct($id, ContainerInterface $container, ConfigManagerInterface $configManager)
    {
        $this->entityId = $id;
        $this->configManager = $configManager;
        $this->container = $container;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        //TODO : find a more efficient way to detect config
        $entity = $args->getEntity();
        if($this->configManager->hasIndices()) {
            # traverse through all indices
            foreach($this->configManager->getIndices() as $key=>$index) {
                if ($this->configManager->hasConfig($index) && get_class($entity) == $this->configManager->getModelClass($index)) {
                    if ($this->isFilterable($entity,  $this->configManager->getModelIndexFilter($index))) {
                        try {
                            if ($this->container->getParameter('rz_search.engine.solr.enabled')) {
                                $this->indexDataSolr('insert', $entity, $index);
                            } elseif ($this->container->getParameter('rz_search.engine.zend_lucene.enabled')) {
                                $this->indexDataZendLucene('insert', $entity, $index);
                            }
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }
                }
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        //TODO : find a more efficient way to detect config
        $entity = $args->getEntity();
        if($this->configManager->hasIndices()) {
            # traverse through all indices
            foreach($this->configManager->getIndices() as $key=>$index) {
                if ($this->configManager->hasConfig($index) && get_class($entity) == $this->configManager->getModelClass($index)) {

                    if ($this->isFilterable($entity,  $this->configManager->getModelIndexFilter($index))) {
                        try {
                            if ($this->container->getParameter('rz_search.engine.solr.enabled')) {
                                $this->indexDataSolr('update', $entity, $index);
                            } elseif ($this->container->getParameter('rz_search.engine.zend_lucene.enabled')) {
                                $this->indexDataZendLucene('update', $entity, $index);
                            }
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    } elseif ($this->isFilterable($entity,  $this->configManager->getModelUnIndexFilter($index))) {
                        try {
                            if ($this->container->getParameter('rz_search.engine.solr.enabled')) {
                                $this->indexDataSolr('update', $entity, $index);
                            } elseif ($this->container->getParameter('rz_search.engine.zend_lucene.enabled')) {
                                $this->indexDataZendLucene('update', $entity, $index, false);
                            }
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }
                }
            }
        }
    }

    protected function isFilterable($entity, $indexFilters) {

        $indexFilterStatus = null;
        if ($indexFilters) {
            foreach($indexFilters as $fieldName=>$filter) {
                $getter = 'get'.ucfirst($fieldName);
                switch ($filter['operand']) {
                    case '=':
                        if(is_null($indexFilterStatus)) {
                            $indexFilterStatus = ($entity->$getter() == $filter['value']) ? true : false;
                        } else {
                            $indexFilterStatus = $indexFilterStatus && ($entity->$getter() == $filter['value']) ? true : false;
                        }
                        break;
                    case '!=':
                        if(is_null($indexFilterStatus)) {
                            $indexFilterStatus =  ($entity->$getter() != $filter['value']) ? true : false;
                        } else {
                            $indexFilterStatus = $indexFilterStatus &&  ($entity->$getter() != $filter['value']) ? true : false;
                        }
                        break;
                }
            }

            return is_null($indexFilterStatus) ? true : $indexFilterStatus;
        } else {
            return true;
        }
    }

    protected function indexDataZendLucene($type, $entity, $entity_id, $isIndex = true)
    {
        $index = $this->container->get('rz_search.zend_lucene')->getIndex($entity_id);
        $id = $this->configManager->getModelIdentifier($entity_id).'_'.$entity->getId();


        if ($type == 'update') {
            $term = new Term($id, 'uuid');
            $docIds = $index->termDocs($term);
            if($docIds) {
                foreach ($docIds as $docId) {
                    $index->delete($docId);
                }
            }
        }

        if($isIndex) {
            // Create a new document
            $doc = new Document();

            $doc->addField(Field::keyword('uuid', $id));
            $doc->addField(Field::keyword('model_id', $entity->getId()));
            $doc->addField(Field::keyword('index_type', $entity_id));

            if($route = $this->configManager->getFieldRouteGenerator($entity_id)) {
                $routeGenerator = $this->container->get($route);
                if($routeGenerator) {
                    $doc->addField(Field::unIndexed('url', $routeGenerator->generate($entity)));
                }
            }

            $indexFields = $this->configManager->getIndexFields($entity_id);
            $searchContent = null;
            foreach ($indexFields as $field) {
                $value = null;
                $settings = $this->configManager->getIndexFieldSettings($entity_id, $field);
                $config['fields'] = isset($settings['fields']) ? $settings['fields'] : null;
                $config['separator'] = isset($config['separator']) ? $config['separator'] : ' ';
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

            // Add your document to the index
            $index->addDocument($doc);
            // Commit your change
            $index->commit();
            // If you want you can optimize your index
            $index->optimize();
        }
    }

    protected function indexDataSolr($type, $entity, $entity_id)
    {
        $this->searchClient = $this->container->get(sprintf('solarium.client.%s', $entity_id));
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
