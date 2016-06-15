<?php

namespace Rz\SearchBundle\Twig\Extension;

use Symfony\Component\Routing\RouterInterface;
use Rz\SearchBundle\Model\ConfigManagerInterface;

class SearchHighlightExtension extends \Twig_Extension
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;
    protected $configManager;
    protected $router;
    protected $defaultTemplates;

    /**
     * @param \Rz\SearchBundle\Model\ConfigManagerInterface $configManager
     * @param \Symfony\Component\Routing\RouterInterface $router
     *
     */
    public function __construct(ConfigManagerInterface $configManager, RouterInterface $router)
    {
        $this->configManager = $configManager;
        $this->router       = $router;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('rz_search_highlight', array($this, 'renderHighlight')),
            new \Twig_SimpleFilter('rz_search_highlight_item', array($this, 'renderHighlightItem')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('rz_search_render_solr_result', array($this, 'renderSolr')),
        );
    }


    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'rz_search';
    }

    public function renderSolr($id, $result, $highlight) {
        $template = $this->configManager->getResultItemTemplate($id, 'solr') ?: $this->getTemplate('result_item');
        return $this->environment->render($template, array('result'=>$result, 'highlighting'=>$highlight));
    }

    /**
     * @param $hightlight
     *
     * @return string
     */
    public function renderHighlight($hightlight)
    {
        $text = array();
        if($hightlight){
            foreach($hightlight as $field => $highlight) {
                $text[] = strip_tags(implode(' (...) ', $highlight), '<span>');
            }
        }
        return implode("\n", $text);
    }

    /**
     * @param $hightlight
     *
     * @return string
     */
    public function renderHighlightItem($hightlight)
    {
        $text = array();
        if($hightlight){
            foreach($hightlight as $field => $highlight) {
                $text[] = strip_tags(implode(' (...) ', $highlight), '<span>');
            }
        }
        return implode("\n", $text);
    }

    /**
     * @return mixed
     */
    public function getDefaultTemplates()
    {
        return $this->defaultTemplates;
    }

    /**
     * @param mixed $defaultTemplates
     */
    public function setDefaultTemplates($defaultTemplates)
    {
        $this->defaultTemplates = $defaultTemplates;
    }

    protected function getTemplates($engine = 'solr') {
        $templates = $this->getDefaultTemplates();
        if (isset($templates[$engine])) {
            return $templates[$engine];
        }
        return null;
    }

    protected function getTemplate($key, $engine='solr') {
        $templates = $this->getTemplates($engine);
        if(!$templates) {
            return null;
        }
        return $templates[$key];
    }
}
