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

class SolrIndexManager extends IndexManager
{
    public function processIndexData($type, $entity, $entityId, $isIndex = true) {

        $searchClient = $this->getSearchClient($entityId);

        try {
            $indexObject = $searchClient->createUpdate();
            $document = $this->indexData($type, $indexObject, $entity, $entityId, $isIndex);
            // add the documents and a commit command to the update query
            $indexObject->addDocuments(array($document));
            $indexObject->addCommit();
            // this executes the query and returns the result
            return $searchClient->update($indexObject);
        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function indexData($type, $indexObject, $entity, $entityId, $isIndex = true) {

        $doc = $indexObject->createDocument();
        $doc->setField('id', $this->getConfigManager()->getModelIdentifier($entityId).'_'.$entity->getId());
        $doc->setField('model_id', $entity->getId());
        $doc->setField('index_type', $entityId);
        // generate route
        $routeGenerator = $this->getRouteGenerator($entityId);
        $doc->setField('url', $routeGenerator->generate($entity));

        $indexFields = $this->getConfigManager()->getIndexFields($entityId);

        foreach ($indexFields as $field) {
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
                //array condition will be depricated on 1.2
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