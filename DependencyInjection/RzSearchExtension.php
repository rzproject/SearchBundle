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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('listener.xml');
        $loader->load('search.xml');
        $loader->load('twig.xml');
        $loader->load('block.xml');
        $this->registerSearchSettings($config, $container);
    }

    /**
     * Registers ckeditor widget.
     *
     */
    protected function registerSearchSettings(array $config, ContainerBuilder $container)
    {
        if (!empty($config['configs'])) {
            $definition = $container->getDefinition('rz_search.config_manager');
            foreach ($config['configs'] as $name => $configuration) {
                if ($configuration['model_class']) {
                    $name = preg_replace('/\\\\/', '.', strtolower($configuration['model_class']));
                    $definition->addMethodCall('setConfig', array($name, $configuration));
                }
            }
        }
    }
}
