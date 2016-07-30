<?php

/*
 * This file is part of the RzSearchBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Rz\SearchBundle\Exception\ConfigManagerException;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class RzSearchExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $config        = $processor->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        //Solr
        $config_solr = $config['engine']['solr'];
        $container->setParameter('rz_search.engine.solr.enabled', $config_solr['enabled']);

        if (($config_solr['enabled'])) {
            $loader->load('registry.xml');

            if ($container->getParameter('kernel.debug') === true) {
                $isDebug = true;
                $loader->load('logger.xml');
            } else {
                $isDebug = false;
            }

            $defaultClient = $config_solr['default_client'];
            if (!count($config_solr['clients'])) {
                $config_solr['clients'][$defaultClient] = array();
            } elseif (count($config_solr['clients']) === 1) {
                $defaultClient = key($config_solr['clients']);
            }

            $endpointReferences = array();
            foreach ($config_solr['endpoints'] as $name => $endpointOptions) {
                $endpointName = sprintf('solarium.client.endpoint.%s', $name);
                $endpointOptions['key'] = $name;
                $container
                    ->setDefinition($endpointName, new Definition('Solarium\Core\Client\Endpoint'))
                    ->setArguments(array($endpointOptions));
                $endpointReferences[$name] = new Reference($endpointName);
            }

            $clients = array();
            foreach ($config_solr['clients'] as $name => $clientOptions) {
                $clientName = sprintf('solarium.client.%s', $name);

                if (isset($clientOptions['client_class'])) {
                    $clientClass = $clientOptions['client_class'];
                    unset($clientOptions['client_class']);
                } else {
                    $clientClass = 'Solarium\Client';
                }
                $clientDefinition = new Definition($clientClass);
                $clients[$name] = new Reference($clientName);

                $container->setDefinition($clientName, $clientDefinition);

                if ($name == $defaultClient) {
                    $container->setAlias('solarium.client', $clientName);
                }

                //If some specific endpoints are given
                if ($endpointReferences) {
                    if (isset($clientOptions['endpoints']) && !empty($clientOptions['endpoints'])) {
                        $endpoints = array();
                        foreach ($clientOptions['endpoints'] as $endpointName) {
                            if (isset($endpointReferences[$endpointName])) {
                                $endpoints[] = $endpointReferences[$endpointName];
                            }
                        }
                    } else {
                        $endpoints = $endpointReferences;
                    }
                    $clientDefinition->setArguments(array(array(
                        'endpoint' => $endpoints,
                    )));
                }

                if (isset($clientOptions['load_balancer']) && $clientOptions['load_balancer']['enabled']) {
                    $loadBalancerDefinition = new Definition('Solarium\Plugin\Loadbalancer\Loadbalancer');
                    $loadBalancerDefinition
                        ->addMethodCall('addEndpoints', array($clientOptions['load_balancer']['endpoints']))
                    ;
                    if (isset($clientOptions['load_balancer']['blocked_query_types'])) {
                        $loadBalancerDefinition
                            ->addMethodCall('setBlockedQueryTypes', array($clientOptions['load_balancer']['blocked_query_types']))
                        ;
                    }

                    $loadBalancerName = $clientName . '.load_balancer';
                    $container->setDefinition($loadBalancerName, $loadBalancerDefinition);

                    $clientDefinition
                        ->addMethodCall('registerPlugin', array('loadbalancer', new Reference($loadBalancerName)))
                    ;
                }

                //Default endpoint
                if (isset($clientOptions['default_endpoint']) && isset($endpointReferences[$clientOptions['default_endpoint']])) {
                    $clientDefinition->addMethodCall('setDefaultEndpoint', array($clientOptions['default_endpoint']));
                }

                //Add the optional adapter class
                if (isset($clientOptions['adapter_class'])) {
                    $clientDefinition->addMethodCall('setAdapter', array($clientOptions['adapter_class']));
                }

                if ($isDebug) {
                    $logger = new Reference('solarium.data_collector');
                    $container->getDefinition($clientName)->addMethodCall('registerPlugin', array($clientName . '.logger', $logger));
                }
            }

            // configure registry
            $registry = $container->getDefinition('solarium.client_registry');
            $registry->replaceArgument(0, $clients);
            if (in_array($defaultClient, array_keys($clients))) {
                $registry->replaceArgument(1, $defaultClient);
            }
        }

        $loader->load('search.xml');
        $loader->load('twig.xml');
        $loader->load('block.xml');
        $loader->load('pagerfanta.xml');
        $this->registerSearchSettings($config, $container);
        $this->configureBlocks($config['blocks'], $container);
        $this->configureSettings($config['settings'], $container);
        $this->configureIndexManager($config['index_manager'], $container);
    }

    /**
     * Registers ckeditor widget.
     *
     */
    protected function registerSearchSettings(array $config, ContainerBuilder $container)
    {
        if (empty($config['configs'])) {
            throw ConfigManagerException::error('At least 1 configuration should be present under configs.');
        }

        $definition = $container->getDefinition('rz_search.manager.config');
        foreach ($config['configs'] as $name => $configuration) {
            if ($configuration['model']['processor'] && $configuration['model']['identifier']) {
                $definition->addMethodCall('setConfig', array($name, $configuration));
                $definition->addMethodCall('setIndex', array($name, $configuration['model']['identifier']));
            }
        }
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureBlocks($config, ContainerBuilder $container)
    {
        $container->setParameter('rz_search.block.search.class', $config['search']['class']);

        # template
        $temp = $config['search']['templates'];
        $templates = array();
        foreach ($temp as $template) {
            $templates[$template['path']] = $template['name'];
        }
        $container->setParameter('rz_search.block.search.templates.default', $templates);
    }

    /**
     * @param array                                                   $config
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return void
     */
    public function configureSettings($config, ContainerBuilder $container)
    {
        $container->setParameter('rz_search.slugify_service',                                   $config['slugify_service']);
        $container->setParameter('rz_search.settings.search.pagination.per_page',               $config['search']['pagination']['per_page']);
        $container->setParameter('rz_search.settings.search.variables.search_query',            $config['search']['variables']['search_query']);
        $container->setParameter('rz_search.settings.search.variables.templates',               $config['search']['templates']);
        $container->setParameter('rz_search.settings.search.variables.default_identifier',      $config['search']['variables']['default_identifier']);

        $container->setParameter('rz_search.settings.search.controller.search',                 $config['search']['controller']['search']);
        $container->setParameter('rz_search.settings.search.controller.ajax',                   $config['search']['controller']['ajax']);
    }

    public function configureIndexManager($config, ContainerBuilder $container)
    {
        $container->setParameter('rz_search.manager.solr.index.class', $config['solr']['class']);
    }
}
