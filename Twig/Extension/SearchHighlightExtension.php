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
        $template = $this->configManager->getResultTemplate($id, 'solr') ?: 'RzSearchBundle:Search:news_solr_results.html.twig';
        $route = $this->configManager->getFieldRoute($id);
        return $this->environment->render($template, array('result'=>$result, 'highlighting'=>$highlight, 'route'=>$route));
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
}
