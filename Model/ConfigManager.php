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

use Rz\SearchBundle\Exception\ConfigManagerException;
use Doctrine\ORM\PersistentCollection;

class ConfigManager extends AbstractConfigManager
{
    /**
     * {@inheritdoc}
     */
    public function getFieldMap($id, $field)
    {
        return isset($this->configs[$id]['field_mapping'][$field]) ? $this->configs[$id]['field_mapping'][$field] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldMapping($id)
    {
        return isset($this->configs[$id]['field_mapping']) ? $this->configs[$id]['field_mapping'] : null;
    }


    /**
     * {@inheritdoc}
     */
    public function getIndexFields($id)
    {
        $indexFields =  null;
        foreach ($this->getConfig($id)['field_mapping'] as $index => $fields) {
            $indexFields[] = $index;
        }
        return $indexFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelProcessor($id)
    {
        return isset($this->configs[$id]['model']['processor']) ? $this->configs[$id]['model']['processor'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelIdentifier($id)
    {
        return isset($this->configs[$id]['model']['identifier']) ? $this->configs[$id]['model']['identifier'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCLIOptions($id)
    {
        return isset($this->configs[$id]['cli']['options']) ? $this->configs[$id]['cli']['options'] : null;
    }


    public function getResultTemplate($id, $type = 'solr')
    {
        return isset($this->configs[$id]['template']['results'][$type]) ? $this->configs[$id]['template']['results'][$type] : null;
    }

    public function getResultItemTemplate($id, $type = 'solr')
    {
        return isset($this->configs[$id]['template']['result_item'][$type]) ? $this->configs[$id]['template']['result_item'][$type] : null;
    }

    public function getEmptyTemplate($id)
    {
        return isset($this->configs[$id]['template']['empty']) ? $this->configs[$id]['template']['empty'] : null;
    }

    public function getNoResultTemplate($id)
    {
        return isset($this->configs[$id]['template']['no_result']) ? $this->configs[$id]['template']['no_result'] : null;
    }

    public function getResultAjaxTemplate($id)
    {
        return isset($this->configs[$id]['template']['result_ajax']) ? $this->configs[$id]['template']['result_ajax'] : null;
    }
}
