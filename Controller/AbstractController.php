<?php

namespace Rz\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\SolariumAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

abstract class AbstractController extends Controller
{
    protected $slugify;
    protected $queryVar;
    protected $configManager;
    protected $translator;
    protected $csrfProvider;
    protected $isIndexEngineEnabled;
    protected $perPage;
    protected $templates;
    protected $searchClient;
    protected $defaultIdentifier;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->init();
    }

    protected function init()
    {
        $this->slugify = $this->get($this->container->getParameter('rz_search.slugify_service'));
        $this->queryVar = $this->slugify->slugify($this->container->getParameter('rz_search.settings.search.variables.search_query'), '_');
        $this->configManager = $this->get('rz_search.manager.config');
        $this->translator = $this->get('translator');
        $this->csrfProvider = $this->container->get('form.csrf_provider');
        $this->isIndexEngineEnabled = $this->container->getParameter('rz_search.engine.solr.enabled');
        $this->perPage = $this->container->getParameter('rz_search.settings.search.pagination.per_page') ?: 5;
        $this->defaultIdentifier = $this->container->getParameter('rz_search.settings.search.variables.default_identifier');
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

    /**
     * @return mixed
     */
    public function getQueryVar()
    {
        return $this->queryVar;
    }

    /**
     * @param mixed $queryVar
     */
    public function setQueryVar($queryVar)
    {
        $this->queryVar = $queryVar;
    }

    /**
     * @return mixed
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @param mixed $configManager
     */
    public function setConfigManager($configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return mixed
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param mixed $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return mixed
     */
    public function getCsrfProvider()
    {
        return $this->csrfProvider;
    }

    /**
     * @param mixed $csrfProvider
     */
    public function setCsrfProvider($csrfProvider)
    {
        $this->csrfProvider = $csrfProvider;
    }

    /**
     * @return mixed
     */
    public function getIsIndexEngineEnabled()
    {
        return $this->isIndexEngineEnabled;
    }

    /**
     * @param mixed $isIndexEngineEnabled
     */
    public function setIsIndexEngineEnabled($isIndexEngineEnabled)
    {
        $this->isIndexEngineEnabled = $isIndexEngineEnabled;
    }

    /**
     * @return mixed
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param mixed $perPage
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
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

    public function getSearchClient($configKey)
    {
        $clientName = sprintf('solarium.client.%s', $configKey);
        $searchClient = $this->container->has($clientName) ? $this->get($clientName) : null;

        if (!$searchClient) {
            return null;
        }

        return $searchClient;
    }

    /**
     * @return mixed
     */
    public function getDefaultIdentifier()
    {
        return $this->defaultIdentifier;
    }

    /**
     * @param mixed $defaultIdentifier
     */
    public function setDefaultIdentifier($defaultIdentifier)
    {
        $this->defaultIdentifier = $defaultIdentifier;
    }

    public function getDefaultTemplates($engine = 'solr')
    {
        $templates = $this->container->getParameter('rz_search.settings.search.variables.templates');
        if (isset($templates[$engine])) {
            return $templates[$engine];
        }
        return null;
    }

    public function getDefaultTemplate($key, $engine='solr')
    {
        $templates = $this->getDefaultTemplates($engine);
        if (!$templates) {
            return null;
        }
        return $templates[$key];
    }
}
