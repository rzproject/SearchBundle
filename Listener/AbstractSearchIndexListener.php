<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Rz\SearchBundle\Model\ConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Rz\SearchBundle\Listener\SearchIndexListenerInterface;
use Sonata\NewsBundle\Model\PostInterface;


abstract class AbstractSearchIndexListener implements SearchIndexListenerInterface
{
    protected $entityId;
    protected $container;

    /**
     * Constructor
     *
     * @param $entityId
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     */
    public function __construct($entityId, ContainerInterface $container){
        $this->entityId = $entityId;
        $this->container = $container;
    }

    public function getConfigManager() {
        return $this->container->get('rz_search.config_manager');
    }

    protected function getIndexManager() {
        if ($this->container->getParameter('rz_search.engine.solr.enabled')) {
            return  $this->container->get('rz_search.manager.solr_index');
        }

        return null;
    }
}
