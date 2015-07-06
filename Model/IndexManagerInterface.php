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

interface IndexManagerInterface
{
    public function isFilterable($entity, $indexFilters);

    public function processCollectionMapping($data, $fields, $filters);

    public function processMapping($item, $fields, $filters);

    public function fieldIsFilterable($item, $filters);

    public function processIndexData($type, $entity, $entityId, $isIndex = true);

    public function indexData($type, $indexObject, $entity, $entity_id, $isIndex = true);
}
