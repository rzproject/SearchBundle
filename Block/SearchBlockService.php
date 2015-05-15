<?php

/*
 * This file is part of the RzMediaBundle package.
 *
 * (c) mell m. zamora <mell@rzproject.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rz\SearchBundle\Block;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\CoreBundle\Validator\ErrorElement;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BaseBlockService;

use Sonata\MediaBundle\Model\MediaManagerInterface;
use Sonata\MediaBundle\Model\MediaInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchBlockService extends BaseBlockService
{
    protected $container;
    protected $templates;
    protected $securityToken;
    protected $securityChecker;

    /**
     * @param string $name
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     */
    public function __construct($name, EngineInterface $templating, ContainerInterface $container)
    {
        parent::__construct($name, $templating);
        $this->container    = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Search Bar';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                   'filter'   => null,
                   'title'   => false,
                   'template' => 'RzSearchBundle:Block:block_search.html.twig'
               ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
       $configs = $this->container->get('rz_search.config_manager')->getConfigNames();

        $formMapper->add('settings', 'sonata_type_immutable_array', array(
                       'keys' => array(
                           array('title', 'text', array('required' => false, 'label'=> 'Title')),
                           array('filter', 'choice', array('choices' => $configs,
                                                           'selectpicker_dropup' => true,
                                                           'label'=> 'Filter')),
                           array('template', 'choice', array('choices' => $this->templates)),
                       )
                   ));
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $query = $this->container->get('request')->query->get('rz_q');

        $parameters =array(
            'rz_q' => $query,
            'block'     => $blockContext->getBlock(),
            'settings'  => $blockContext->getSettings()
        );

        if ($this->securityChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->renderPrivateResponse($blockContext->getTemplate(), $parameters, $response);
        }

        return $this->renderResponse($blockContext->getTemplate(), $parameters, $response);
    }


    /**
     * @param ErrorElement   $errorElement
     * @param BlockInterface $block
     *
     * @return void
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block){

    }

    /**
     * @return mixed
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param mixed $templates
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }

    /**
     * @return mixed
     */
    public function getSecurityToken()
    {
        return $this->securityToken;
    }

    /**
     * @param mixed $securityToken
     */
    public function setSecurityToken($securityToken)
    {
        $this->securityToken = $securityToken;
    }

    /**
     * @return mixed
     */
    public function getSecurityChecker()
    {
        return $this->securityChecker;
    }

    /**
     * @param mixed $securityChecker
     */
    public function setSecurityChecker($securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }
}
