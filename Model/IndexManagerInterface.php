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
    public function getContainer();

    public function setContainer($container);

    public function getConfigManager();

    public function getModelProcessor($configKey);

    public function getSearchClient($entityId);

    public function processIndexData($processor, $searchClient, $entity, $configKey);

    public function indexData($processor, $indexObject, $entity, $configKey);
}
