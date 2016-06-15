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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('rz_search');
        $this->addBundleSettings($node);
        $this->addBlockSettings($node);
        $this->addIndexSettings($node);
        return $treeBuilder;
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addBundleSettings(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('settings')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('slugify_service')
                            ->info('You should use: sonata.core.slugify.cocur, but for BC we keep \'sonata.core.slugify.native\' as default')
                            ->defaultValue('sonata.core.slugify.cocur')
                        ->end()
                        ->arrayNode('search')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('pagination')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('per_page')->cannotBeEmpty()->defaultValue(5)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('variables')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('search_query')->cannotBeEmpty()->defaultValue("q")->end()
                                        ->scalarNode('default_identifier')->cannotBeEmpty()->end()
                                    ->end()
                                ->end()

                                ->arrayNode('controller')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('search')->defaultValue("RzSearchBundle:Search:search")->end()
                                        ->scalarNode('ajax')->defaultValue('RzSearchBundle:Helper:fetchData')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('templates')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->arrayNode('solr')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('results')->cannotBeEmpty()->defaultValue("RzSearchBundle:Search:Solr\\results.html.twig")->end()
                                                ->scalarNode('result_item')->cannotBeEmpty()->defaultValue("RzSearchBundle:Search:Solr\\result_item.html.twig")->end()
                                                ->scalarNode('results_ajax')->cannotBeEmpty()->defaultValue("RzSearchBundle:Search:Solr\\results_ajax.html.twig")->end()
                                                ->scalarNode('empty')->cannotBeEmpty()->defaultValue("RzSearchBundle:Search:Solr\\empty.html.twig")->end()
                                                ->scalarNode('no_results')->cannotBeEmpty()->defaultValue("RzSearchBundle:Search:Solr\\no_results.html.twig")->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('engine')
                    ->children()
                        ->arrayNode('solr')
                            ->children()
                                ->scalarNode('enabled')->defaultValue(true)->end()
                                ->scalarNode('default_client')->cannotBeEmpty()->defaultValue('default')->end()
                                ->arrayNode('endpoints')
                                    ->canBeUnset()
                                    ->useAttributeAsKey('name')
                                    ->prototype('array')
                                        ->beforeNormalization()
                                            ->ifTrue(function($v) {
                                                return isset($v['dsn']);
                                            })
                                            ->then(function($v) {
                                                $parsed_dsn = parse_url($v['dsn']);
                                                unset($v['dsn']);
                                                if ($parsed_dsn) {
                                                    if (isset($parsed_dsn['host'])) {
                                                        $v['host'] = $parsed_dsn['host'];
                                                    }
                                                    if (isset($parsed_dsn['user'])) {
                                                        $auth = $parsed_dsn['user'] . (isset($parsed_dsn['pass']) ? ':' . $parsed_dsn['pass'] : '');
                                                        $v['host'] = $auth . '@' . $v['host'];
                                                    }

                                                    $v['port'] = isset($parsed_dsn['port']) ? $parsed_dsn['port'] : 80;
                                                    $v['path'] = isset($parsed_dsn['path']) ? $parsed_dsn['path'] : '';
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                                            ->scalarNode('port')->defaultValue(8983)->end()
                                            ->scalarNode('path')->defaultValue('/solr')->end()
                                            ->scalarNode('core')->end()
                                            ->scalarNode('timeout')->defaultValue(5)->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('clients')
                                    ->canBeUnset()
                                    ->useAttributeAsKey('name')
                                    ->prototype('array')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('client_class')->cannotBeEmpty()->defaultValue('Solarium\Client')->end()
                                            ->scalarNode('adapter_class')->end()
                                            ->arrayNode('endpoints')
                                                ->beforeNormalization()
                                                    ->ifString()
                                                    ->then(function($v) { return preg_split('/\s*,\s*/', $v); })
                                                ->end()
                                                ->prototype('scalar')->end()
                                            ->end()
                                            ->scalarNode('default_endpoint')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('configs')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->useAttributeAsKey('name')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addBlockSettings(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('blocks')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('search')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue('Rz\\SearchBundle\\Block\\SearchBlockService')->end()
                                ->arrayNode('templates')
                                    ->useAttributeAsKey('id')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('name')->defaultValue('default')->end()
                                            ->scalarNode('path')->defaultValue('RzSearchBundle:Block:Search\\default.html.twig')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addIndexSettings(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('index_manager')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('solr')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue('Rz\\SearchBundle\\Model\\SolrIndexManager')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
