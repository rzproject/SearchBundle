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

class SearchManager implements SearchManagerInterface
{
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     *
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getConfigManager()
    {
        return $this->configManager;
    }
}
