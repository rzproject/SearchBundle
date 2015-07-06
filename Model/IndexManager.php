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
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class IndexManager implements IndexManagerInterface
{
    protected $container;

    /**
     * Constructor
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param mixed $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function getConfigManager() {
        return $this->container->get('rz_search.config_manager');
    }

    public function getRouteGenerator($entity_id) {
        return $this->container->get($this->getConfigManager()->getFieldRouteGenerator($entity_id));
    }

    public function isFilterable($entity, $indexFilters) {
        $indexFilterStatus = null;
        if ($indexFilters) {
            foreach($indexFilters as $fieldName=>$filter) {
                $getter = 'get'.ucfirst($fieldName);
                switch ($filter['operand']) {
                    case '=':
                        if(is_null($indexFilterStatus)) {
                            $indexFilterStatus = ($entity->$getter() == $filter['value']) ? true : false;
                        } else {
                            $indexFilterStatus = $indexFilterStatus && ($entity->$getter() == $filter['value']) ? true : false;
                        }
                        break;
                    case '!=':
                        if(is_null($indexFilterStatus)) {
                            $indexFilterStatus =  ($entity->$getter() != $filter['value']) ? true : false;
                        } else {
                            $indexFilterStatus = $indexFilterStatus &&  ($entity->$getter() != $filter['value']) ? true : false;
                        }
                        break;
                }
            }

            return is_null($indexFilterStatus) ? true : $indexFilterStatus;
        } else {
            return true;
        }
    }

    public function fieldIsFilterable($item, $filters) {
        $is_indexed = null;
        foreach ($filters as $index =>$filter) {
            if (array_key_exists('operand', $filter) && array_key_exists('value', $filter)) {
                $val = false;
                $getter = 'get'.ucfirst($index);
                switch ($filter['operand']) {
                    case '=':
                        $val = ($item->$getter() == $filter['value']) ? true : false;
                        break;
                    case '!=':
                        $val = ($item->$getter() != $filter['value']) ? true : false;
                        break;
                }
                $is_indexed =  (null === $is_indexed) ? $val : ($is_indexed && $val);
            } else {
                continue;
            }
        }
        return $is_indexed;
    }

    public function processCollectionMapping($data, $fields, $filters) {
        $collection = null;
        foreach ($data as $item) {
            $temp = $this->processMapping($item, $fields, $filters);
            if ($temp) {
                $collection[] = $temp;
            }
        }

        return $collection ? implode(',', $collection) : null;
    }

    public function processMapping($item, $fields, $filters) {
        if (is_array($fields)) {
            $collection = null;
            foreach ($fields as $field) {
                if (is_array($filters)) {
                    if ($this->fieldIsFilterable($item, $filters)) {
                        $getter = 'get'.ucfirst($field);
                        $collection[] =  $item->$getter();
                    } else {
                        continue;
                    }
                } else {
                    $getter = 'get'.ucfirst($field);
                    $collection[] =  $item->$getter();
                }
            };

            return $collection ? implode(',', $collection) : null;
        } else {
            $getter = 'get'.ucfirst($fields);

            return $item->$getter();
        }
    }

    protected function getSearchClient($entityId) {
        if ($this->container->getParameter('rz_search.engine.solr.enabled')) {
            return  $this->container->get(sprintf('solarium.client.%s', $entityId));
        }

        if ($this->container->getParameter('rz_search.engine.zend_lucene.enabled')) {
            return  $this->container->get('rz_search.zend_lucene')->getIndex($entityId);
        }

        return null;
    }
}