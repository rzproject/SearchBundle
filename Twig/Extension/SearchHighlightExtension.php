<?php

namespace Rz\SearchBundle\Twig\Extension;

use Symfony\Component\Routing\RouterInterface;

class SearchHighlightExtension extends \Twig_Extension
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('rz_search_highlight', array($this, 'renderHighlight')),
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
                $text[] = strip_tags(implode(' (...) ', $highlight), '<b>');
            }
        }
        return implode("\n", $text);
    }
}
