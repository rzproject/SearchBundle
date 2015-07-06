<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\FieldProcessor;

use  Rz\SearchBundle\Model\ConfigManagerInterface;

interface FieldProcessorInterface
{
    public function processFieldIndexValue($entityId, $object, $field, $options = array());

    public function setConfigManager(ConfigManagerInterface $configManager);
}