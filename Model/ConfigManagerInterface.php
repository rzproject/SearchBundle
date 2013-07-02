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

interface ConfigManagerInterface
{
    /**
     * Checks if the CKEditor configs exists.
     *
     * @return boolean TRUE if the CKEditor configs exists else FALSE.
     */
    public function hasConfigs();

    /**
     * Gets the CKEditor configs.
     *
     * @return array The CKEditor configs.
     */
    public function getConfigs();

    /**
     * Sets the CKEditor configs.
     *
     * @param array $configs The CKEditor configs.
     */
    public function setConfigs(array $configs);

    /**
     * Checks if a specific CKEditor config exists.
     *
     * @param string $name The CKEditor config name.
     *
     * @return array TRUE if the CKEditor config exists else FALSE.
     */
    public function hasConfig($name);

    /**
     * Gets a specific CKEditor config.
     *
     * @param string $name The CKEditor config name.
     *
     * @return array The CKEditor config.
     */
    public function getConfig($name);

    /**
     * Sets a CKEditor config.
     *
     * @param string $name   The CKEditor config name.
     * @param array  $config The CKEditor config.
     */
    public function setConfig($name, array $config);
}
