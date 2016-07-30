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

class SolrIndexManager extends AbstractIndexManager
{
    public function processIndexData($processor, $searchClient, $entity, $configKey)
    {
        try {
            $indexObject = $searchClient->createUpdate();
            $document = $this->indexData($processor, $indexObject, $entity, $configKey);

            // add the documents and a commit command to the update query
            $indexObject->addDocuments(array($document));
            $indexObject->addCommit();
            // this executes the query and returns the result
            return $searchClient->update($indexObject);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function indexData($processor, $indexObject, $entity, $configKey)
    {
        $doc = $indexObject->createDocument();

        $values = $processor->process($configKey, $entity);
        $fieldMappings = $this->getConfigManager()->getFieldMapping($configKey);

        foreach ($fieldMappings as $key=>$field) {
            try {
                $doc->setField($key, $values[$key]);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $doc;
    }
}
