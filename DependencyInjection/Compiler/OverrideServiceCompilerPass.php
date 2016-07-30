<?php

namespace Rz\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        #####################
        # override blocks
        #####################
        #set slugify service
        $serviceId = $container->getParameter('rz_search.slugify_service');

        # Search - This is how to do it to prevent BC-BREAK
        $definition = $container->getDefinition('rz.search.block.search');

        if (interface_exists('Sonata\PageBundle\Model\BlockInteractorInterface')) {
            $blocks = $container->getParameter('sonata_block.blocks');
            $blockService = 'rz.search.block.search';
            if (isset($blocks[$blockService]) && isset($blocks[$blockService]['templates'])) {
                $container->setParameter('rz_search.block.search.templates', $blocks[$blockService]['templates']);
            }
        }

        $blockTemplates = $container->getParameter('rz_search.block.search.templates');

        $templates = [];
        foreach ($blockTemplates as $item) {
            $templates[$item['template']] = $item['name'];
        }

        if (!$templates) {
            $templates = $container->getParameter('rz_search.block.search.templates.default');
        }

        $definition->addMethodCall('setTemplates', array($templates));
        $definition->addMethodCall('setSlugify', array(new Reference($serviceId)));
        $definition->addMethodCall('setSecurityToken', array(new Reference('security.token_storage')));
        $definition->addMethodCall('setSecurityChecker', array(new Reference('security.authorization_checker')));
    }
}
