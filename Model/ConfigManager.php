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

use Rz\CkeditorBundle\Exception\ConfigManagerException;
use Doctrine\ORM\PersistentCollection;

class ConfigManager implements ConfigManagerInterface
{
    /** @var array */
    protected $configs;
    protected $options;

    /**
     * Creates a CKEditor config manager.
     *
     * @param array $configs The CKEditor configs.
     */
    public function __construct(array $configs = array())
    {
        $this->setConfigs($configs);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfigs()
    {
        return !empty($this->configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigs(array $configs)
    {
        foreach ($configs as $name => $config) {
            $this->setConfig($name, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfig($name)
    {
        return isset($this->configs[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($name)
    {
        if (!$this->hasConfig($name)) {
            throw ConfigManagerException::configDoesNotExist($name);
        }

        return $this->configs[$name];
    }

    /**
 * {@inheritdoc}
 */
    public function setConfig($name, array $config)
    {
        $this->configs[$name] = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function hasFieldMapSettings($model_id, $field)
    {
        if (isset($this->configs[$model_id]['field_map_settings'][$field])) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldMap($model_id, $field)
    {
      return isset($this->configs[$model_id]['field_mapping'][$field]) ? $this->configs[$model_id]['field_mapping'][$field] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue($model_id, $entity, $field)
    {
        $getter = 'get'.ucfirst($this->getFieldMap($model_id, $field));
        if (!method_exists($entity, $getter)) {
            throw new \RuntimeException(sprintf("Class '%s' should have a method '%s'.", get_class($entity), $getter));
        }

        return $entity->$getter();
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationValue($entity_id, $entity, $field)
    {
        $children = $this->getFieldValue($entity_id, $entity, $field);
        if ($children instanceof PersistentCollection) {
            $value = null;
            foreach ($children as $child) {
                $value[] =  $this->getAssocFieldValue($child, $this->getFieldMapSettings($entity_id, $field));
            }

            return $value ? implode(',', $value) : null;
        } else {
            return $this->getAssocFieldValue($children, $this->getFieldMapSettings($entity_id, $field));
        }
    }

    public function getAssocFieldValue($entity, $settings)
    {
        if (is_array($settings['fields'])) {
            $value = null;
            foreach ($settings['fields'] as $field) {
                $getter = $this->getter($field);
                if (!method_exists($entity, $getter)) {
                    throw new \RuntimeException(sprintf("Class '%s' should have a method '%s'.", get_class($entity), $getter));
                }
                $value[] =  $entity->$getter();
            }

            return $value ? implode(',', $value) : null;
        } else {
            $getter = $this->getter($settings['fields']);
            if (!method_exists($entity, $getter)) {
                throw new \RuntimeException(sprintf("Class '%s' should have a method '%s'.", get_class($entity), $getter));
            }

            return $entity->$getter();
        }
    }

    protected function getter($field)
    {
        return 'get'.ucfirst($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldMapSettings($model_id, $field)
    {
        return isset($this->configs[$model_id]['field_map_settings'][$field]) ? $this->configs[$model_id]['field_map_settings'][$field] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexFields($model_id)
    {
        $indexFields =  null;
        foreach ($this->getConfig($model_id)['field_mapping'] as $index => $fields) {
            $indexFields[] = $index;
        }

        return $indexFields;
    }
}
