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

class ConfigManager implements ConfigManagerInterface
{
    /** @var array */
    protected $configs;
    protected $options;
    protected $indices;

    /**
     * Creates a CKEditor config manager.
     *
     * @param array $configs The CKEditor configs.
     */
    public function __construct(array $configs = array())
    {
        $this->setConfigs($configs);
        $this->options = array();
        $this->indices= array();
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
    public function hasConfigInConfigs($name, $config)
    {
        return isset($config[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigInConfigs($name, $config)
    {
        if($this->hasConfigInConfigs($name, $config)) {
            return $config[$name];
        } else {
            return;
        }

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
    public function getFieldMap($model_id, $field)
    {
        return isset($this->configs[$model_id]['field_mapping'][$field]) ? $this->configs[$model_id]['field_mapping'][$field] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelIdentifier($model_id)
    {
        return isset($this->configs[$model_id]['model_identifier']) ? $this->configs[$model_id]['model_identifier'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelManager($model_id)
    {
        return isset($this->configs[$model_id]['model_manager']) ? $this->configs[$model_id]['model_manager'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaManager($model_id)
    {
        return isset($this->configs[$model_id]['media_manager']) ? $this->configs[$model_id]['media_manager'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelClass($model_id)
    {
        return isset($this->configs[$model_id]['model_class']) ? $this->configs[$model_id]['model_class'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getModelIndexFilter($model_id)
    {
        return isset($this->configs[$model_id]['model_index_filter']) ? $this->configs[$model_id]['model_index_filter'] : null;
    }


    /**
     * {@inheritdoc}
     */
    public function getModelUnIndexFilter($model_id)
    {
        return isset($this->configs[$model_id]['model_unindex_filter']) ? $this->configs[$model_id]['model_unindex_filter'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldValue($model_id, $entity, $field, $config = null)
    {
        $getter = 'get'.ucfirst($this->getFieldMap($model_id, $field));
        if (!method_exists($entity, $getter)) {
            throw new \RuntimeException(sprintf("Class '%s' should have a method '%s'.", get_class($entity), $getter));
        }


        $value = $entity->$getter();

        if ($value instanceof PersistentCollection) {
            $fields = isset($config['fields']) ? $config['fields'] : null;
            $separator = isset($config['separator']) ? $config['separator'] : ' ';

			/*
			 Handle this Lucene index mapping configuration:

			 field_map_settings:
				 postHasCategory:
					 fields :
					   - category:   <-- Relation Field
						  - slug	 <-- Relation Return Field
					 separator: ~
					 filter : ~
					 type : unStored

			*/

            $temp = null;
            if(is_array($fields) && count($fields)>0){
                if(count($value)>0) {
                    foreach ($value as $child) {
                        foreach($fields as $field){
                            if(is_array($field)) {
                                foreach($field as $keyField=>$keyValues){
                                    $sub = $this->getter($keyField);

                                    if(is_array($keyValues)){
                                        foreach($keyValues as $relationField){
                                            $relation = $this->getter($relationField);
                                            $temp[] = $child->$sub()->$relation();
                                        }
                                    }
                                }
                            } else {
                                $relation = $this->getter($field);
                                $temp[] = $child->$relation();
                            }
                        }
                    }
                }
			} else {
                if(count($value)>0) {
                    foreach ($value as $child) {
                        $temp[] =  $child->__toString();
                    }
                }
			}
			return $temp ? implode('~', $temp) : null;
        } elseif ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        } elseif(is_object($value)) {
            $fields = isset($config['fields']) ? $config['fields'] : null;
            $separator = isset($config['separator']) ? $config['separator'] : ' ';
            if(count($fields)>0){
                $temp = null;
                foreach ($fields as $child) {
                    $getterChild = $this->getter($child);
                    $temp[] =  $value->$getterChild();
                }
                return $temp ? implode($separator, $temp) : null;
            }
            return $value->__toString();
        } elseif(is_array($value)) {
            $fields = isset($config['fields']) ? $config['fields'] : null;
            $separator = isset($config['separator']) ? $config['separator'] : ' ';
            if($fields) {
                $temp = null;
                if(count($fields)) {
                    foreach ($fields as $child) {
                        if(array_key_exists($child,$value)) {

                            if(is_array($value[$child])) {
                                $temp[] =  implode($separator, $value[$child]);
                            } else {
                                $temp[] =  $value[$child];
                            }
                        }
                    }
                }
                return $temp ? implode($separator, $temp) : null;
            } else {
                return;
            }
        } else {
            return $value;
        }
    }

    protected function getter($field)
    {
        return 'get'.ucfirst($field);
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

    /**
     * {@inheritdoc}
     */
    public function getConfigNames()
    {
        $indexFields =  null;

        foreach ($this->getConfigs() as $index => $config) {
            $indexFields[$index] = $config['label'];
        }
        return $indexFields;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexFieldSettings($model_id, $field)
    {
        if (array_key_exists($field, $this->getConfig($model_id)['field_map_settings'])) {
            return $this->getConfig($model_id)['field_map_settings'][$field];
        } else {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexFieldSettingsProcessor($model_id, $field)
    {
        if($map = $this->getFieldMap($model_id, $field)) {
            if (array_key_exists($map, $this->getConfig($model_id)['field_map_settings']) &&
                isset($this->getConfig($model_id)['field_map_settings'][$map]['processor']) &&
                isset($this->getConfig($model_id)['field_map_settings'][$map]['processor']['service'])) {
                return $this->getConfig($model_id)['field_map_settings'][$map]['processor']['service'];
            }
        }
        return;
    }

    public function getIndexFieldSettingsProcessorOptions($model_id, $field)
    {
        if($map = $this->getFieldMap($model_id, $field)) {
            if (array_key_exists($map, $this->getConfig($model_id)['field_map_settings']) &&
                isset($this->getConfig($model_id)['field_map_settings'][$map]['processor']) &&
                isset($this->getConfig($model_id)['field_map_settings'][$map]['processor']['options'])) {
                return $this->getConfig($model_id)['field_map_settings'][$map]['processor']['options'];
            }
        }
        return;
    }

    public function getResultTemplate($model_id, $type = 'solr')
    {
        return isset($this->configs[$model_id]['template']['result'][$type]) ? $this->configs[$model_id]['template']['result'][$type] : null;
    }

    public function getSearchTemplate($model_id, $type = 'solr')
    {
        return isset($this->configs[$model_id]['template']['search'][$type]) ? $this->configs[$model_id]['template']['search'][$type] : null;
    }

    public function getEmptyTemplate($model_id)
    {
        return isset($this->configs[$model_id]['template']['empty']) ? $this->configs[$model_id]['template']['empty'] : null;
    }

    public function getNoResultTemplate($model_id)
    {
        return isset($this->configs[$model_id]['template']['no_result']) ? $this->configs[$model_id]['template']['no_result'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldRouteGenerator($model_id)
    {
        return isset($this->configs[$model_id]['route_generator']) ? $this->configs[$model_id]['route_generator'] : null;
    }

    public function getFieldRoute($model_id)
    {
        return isset($this->configs[$model_id]['route']) ? $this->configs[$model_id]['route'] : null;
    }

    public function getModelId($id) {

        return isset($this->configs[$id]['model_class']) ? preg_replace('/\\\\/', '.', strtolower($this->configs[$id]['model_class'])) : null;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        foreach ($options as $name => $option) {
            $this->setOption($name, $option);
        }
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOptions()
    {
        return !empty($this->options);
    }


    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            throw ConfigManagerException::optionDoesNotExist($name);
        }

        return $this->options[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($name, $option)
    {
        $this->options[$name] = $option;
    }

    /**
     * @param mixed $indices
     */
    public function setIndices(array $indices)
    {
        foreach ($indices as $name => $index) {
            $this->setIndex($name, $index);
        }
    }

    /**
     * @return mixed
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndices()
    {
        return !empty($this->indices);
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndex($name)
    {
        return isset($this->indices[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndex($name)
    {
        if (!$this->hasIndex($name)) {
            throw ConfigManagerException::indexDoesNotExist($name);
        }

        return $this->indices[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setIndex($name, $index)
    {
        $this->indices[$name] = $index;
    }
}
