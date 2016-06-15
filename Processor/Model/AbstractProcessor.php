<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\Processor\Model;

use  Rz\SearchBundle\Model\ConfigManagerInterface;

class AbstractProcessor implements ProcessorInterface
{
    protected $configManager;

    public function process($configKey, $entity, $options=[]){}

    public function fetchData($criteria = []){}

    public function fetchAllData($criteria = []){}

    /**
     * @return mixed
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @param mixed $configManager
     */
    public function setConfigManager(ConfigManagerInterface $configManager)
    {
        $this->configManager = $configManager;
    }
}

