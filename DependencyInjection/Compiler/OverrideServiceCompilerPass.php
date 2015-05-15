<?php

namespace Rz\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) {
        #####################
        # override blocks
        #####################

        # Search - This is how to do it to prevent BC-BREAK
        $definition = $container->getDefinition('rz.search.block.search');
        $definition->addMethodCall('setTemplates', array($container->getParameter('rz_search.block.search.templates')));
        $definition->addMethodCall('setSecurityToken', array(new Reference('security.token_storage')));
        $definition->addMethodCall('setSecurityChecker', array(new Reference('security.authorization_checker')));
    }
}
