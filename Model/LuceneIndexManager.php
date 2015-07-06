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

class LuceneIndexManager extends IndexManager
{
    public function indexData($type, $entity, $entityId, $isIndex = true) {

        $searchClient = $this->getSearchClient($entityId);
        $id =  $this->getConfigManager()->getModelIdentifier($entityId).'_'.$entity->getId();

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
            $doc->addField(Field::keyword('index_type', $entityId));

            $routeGenerator = $this->getRouteGenerator($entityId);

            if($routeGenerator) {
                $doc->addField(Field::unIndexed('url', $routeGenerator->generate($entity)));
            }

            $indexFields =  $this->getConfigManager()->getIndexFields($entityId);
            $searchContent = null;

            if(is_array($indexFields) && count($indexFields)>0) {
                foreach ($indexFields as $field) {
                    $value = null;
                    $settings =  $this->getConfigManager()->getIndexFieldSettings($entityId, $field);
                    $value = null;
                    //USE FIELD PROCESSOR
                    $processorService = $this->getConfigManager()->getIndexFieldSettingsProcessor($entityId, $field);
                    if($processorService) {
                        if($this->getContainer()->has($processorService)) {
                            $processor = $this->getContainer()->get($processorService);
                            if($processor instanceof FieldProcessorInterface) {
                                $processorOptions = $this->getConfigManager()->getIndexFieldSettingsProcessorOptions($entityId, $field) ?: array();
                                $value = $processor->processFieldIndexValue($entityId, $entity, $field, $processorOptions);
                            }
                        }
                    } else {
                        $value = $this->getConfigManager()->getFieldValue($entityId, $entity, $field);
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

            // Add your document to the index
            $searchClient->addDocument($doc);
            // Commit your change
            $searchClient->commit();
            // If you want you can optimize your index
            $searchClient->optimize();

            return $searchClient;
        }
    }
}