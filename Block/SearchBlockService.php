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
use Sonata\CoreBundle\Model\Metadata;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BaseBlockService;

use Sonata\MediaBundle\Model\MediaManagerInterface;
use Sonata\MediaBundle\Model\MediaInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SearchBlockService extends BaseBlockService
{
    protected $container;
    protected $templates;
    protected $securityToken;
    protected $securityChecker;
    protected $slugify;

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
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'filter'   => null,
            'title'    => null,
            'help'     => null,
            'template' => 'RzSearchBundle:Block:Search\default.html.twig'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $configs = $this->container->get('rz_search.manager.config')->getConfigNames();
        $trans = $this->container->get('translator');

        $formMapper->add('settings', 'sonata_type_immutable_array', array(
                       'keys' => array(
                           array('title', 'text', array('required' => false, 'label'=> $trans->trans('form.search_block.title', array(),  $this->getBlockMetadata()->getDomain()))),
                           array('help',  'text', array('required' => false, 'label'=> $trans->trans('form.search_block.help', array(),  $this->getBlockMetadata()->getDomain()))),
                           array('filter', 'choice', array('choices' => $configs,
                                                           'label'=> $trans->trans('form.search_block.filter', array(),  $this->getBlockMetadata()->getDomain()))),
                           array('template', 'choice', array('choices' => $this->templates)),
                       )
                   ));
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $queryVar = $this->getSlugify()->slugify($this->container->getParameter('rz_search.settings.search.variables.search_query'), '_');
        $query = $this->container->get('request')->query->get($queryVar) ?: null;

        $form = $this->createFormBuilder()
            ->add($this->getQueryVar(),  TextType::class, array('data'=>$query))
            ->add('type',   HiddenType::class, array('data'=>$this->getDefaultIdentifier()))
            ->add('search', SubmitType::class, array('label' => 'btn.search'))
            ->getForm();


        $csrfProvider = $this->container->get('form.csrf_provider');

        $parameters =array(
            'form'           => $form->createView(),
            'query_var'      => $queryVar,
            'block_context'  => $blockContext,
            'block'          => $blockContext->getBlock(),
        );

        $template = $blockContext->getBlock()->getSetting('template');

        if ($this->securityChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->renderPrivateResponse($template, $parameters, $response);
        }

        return $this->renderResponse($template, $parameters, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (!is_null($code) ? $code : $this->getName()), false, 'RzSearchBundle', array(
            'class' => 'fa fa-fw fa-search',
        ));
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    public function createFormBuilder($data = null, array $options = array())
    {
        $type = 'Symfony\Component\Form\Extension\Core\Type\FormType';
        return $this->container->get('form.factory')->createBuilder($type, $data, $options);
    }


    /**
     * @param ErrorElement   $errorElement
     * @param BlockInterface $block
     *
     * @return void
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block){

    }

    public function getDefaultIdentifier() {
        return  $this->container->getParameter('rz_search.settings.search.variables.default_identifier');
    }

    public function getQueryVar() {
        return $this->slugify->slugify($this->container->getParameter('rz_search.settings.search.variables.search_query'), '_');
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

    /**
     * @return mixed
     */
    public function getSlugify()
    {
        return $this->slugify;
    }

    /**
     * @param mixed $slugify
     */
    public function setSlugify($slugify)
    {
        $this->slugify = $slugify;
    }
}
