<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\Exception;

class ConfigManagerException extends \Exception
{
    /**
     * Gets the "CONFIG DOES NOT EXIST" exception.
     *
     * @param string $name The invalid CKEditor config name.
     *
     * @return \Rz\Exception\ConfigManagerException The "CONFIG DOES NOT EXIST" exception.
     */
    public static function configDoesNotExist($name)
    {
        return new static(sprintf('The RzSearchBundle config "%s" does not exist.', $name));
    }

    /**
     * Gets the "CONFIG DOES NOT EXIST" exception.
     *
     * @param string $name The invalid CKEditor config name.
     *
     * @return \Rz\Exception\ConfigManagerException The "CONFIG DOES NOT EXIST" exception.
     */
    public static function indexDoesNotExist($name)
    {
        return new static(sprintf('The RzSearchBundle index "%s" does not exist.', $name));
    }

    /**
     * Gets the "CONFIG DOES NOT EXIST" exception.
     *
     * @param string $name The invalid CKEditor config name.
     *
     * @return \Rz\Exception\ConfigManagerException The "CONFIG DOES NOT EXIST" exception.
     */
    public static function optionDoesNotExist($name)
    {
        return new static(sprintf('The RzSearchBundle options "%s" does not exist.', $name));
    }
}
