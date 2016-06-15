<?php

namespace Rz\SearchBundle\Model;

use Rz\SearchBundle\Exception\ConfigManagerException;
use Doctrine\ORM\PersistentCollection;

class AbstractConfigManager implements ConfigManagerInterface
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

    protected function getter($field)
    {
        return 'get'.ucfirst($field);
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
