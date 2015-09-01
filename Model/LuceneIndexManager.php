<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\Model;

use Rz\SearchBundle\FieldProcessor\FieldProcessorInterface;
use ZendSearch\Lucene\Index\Term;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Document\Field;

class LuceneIndexManager extends IndexManager
{

    public function processIndexData($type, $entity, $entityId, $isIndex = true) {

        $indexObject = $this->getSearchClient($entityId);

        try {
            $document = $this->indexData($type, $indexObject, $entity, $entityId, $isIndex);
            // add the documents and a commit command to the update query
            $indexObject->addDocument($document);
            $indexObject->commit();
            // If you want you can optimize your index
            $indexObject->optimize();
            // this executes the query and returns the result
            return $indexObject;
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function indexData($type, $indexObject, $entity, $entity_id, $isIndex = true) {

        $searchClient = $this->getSearchClient($entity_id);
        $id =  $this->getConfigManager()->getModelIdentifier($entity_id).'_'.$entity->getId();

        if ($type == 'update') {
            $term = new Term($id, 'uuid');
            $docIds = $searchClient->termDocs($term);
            if(is_array($docIds) && count($docIds) > 0) {
                foreach ($docIds as $docId) {
                    $searchClient->delete($docId);
                }
            }
        }

        if($isIndex) {
            // Create a new document
            $doc = new Document();

            $doc->addField(Field::keyword('uuid', $id));
            $doc->addField(Field::keyword('model_id', $entity->getId()));
            $doc->addField(Field::keyword('index_type', $entity_id));

            $routeGenerator = $this->getRouteGenerator($entity_id);

            if($routeGenerator) {
                $doc->addField(Field::unIndexed('url', $routeGenerator->generate($entity)));
            }

            $indexFields =  $this->getConfigManager()->getIndexFields($entity_id);
            $searchContent = null;

            if(is_array($indexFields) && count($indexFields)>0) {
                foreach ($indexFields as $field) {
                    $value = null;
                    $settings =  $this->getConfigManager()->getIndexFieldSettings($entity_id, $field);
                    $value = null;
                    //USE FIELD PROCESSOR
                    $processorService = $this->getConfigManager()->getIndexFieldSettingsProcessor($entity_id, $field);
                    if($processorService) {
                        if($this->getContainer()->has($processorService)) {
                            $processor = $this->getContainer()->get($processorService);
                            if($processor instanceof FieldProcessorInterface) {
                                $processorOptions = $this->getConfigManager()->getIndexFieldSettingsProcessorOptions($entity_id, $field) ?: array();
                                $value = $processor->processFieldIndexValue($entity_id, $entity, $field, $processorOptions);
                            }
                        }
                    } else {
                        $value = $this->getConfigManager()->getFieldValue($entity_id, $entity, $field);
                    }

                    try {
                        if (is_array($value)) {
                            foreach ($value as $val) {
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
            }
            //default search field
            $doc->addField(Field::unStored('searchContent', $searchContent));

            return $doc;
        }
    }
}