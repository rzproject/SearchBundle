<?php

namespace Rz\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\SolariumAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class SearchController extends Controller
{
    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return RedirectResponse
     */
    public function searchAction(Request $request)
    {
        $search = $request->query->get('rz_q');
        $type = $request->query->get('rz_type');

        $configManager = $this->container->get('rz_search.config_manager');

        if ( $search === '') {
            $response = $this->render($configManager->getEmptyTemplate($type) ?: 'RzSearchBundle::empty.html.twig');
            return $response;
        }

        if ($search === NULL) {
            $response = $this->render($configManager->getNoResultTemplate($type) ?: 'RzSearchBundle::no_results.html.twig');
            return $response;
        }

        $search =  preg_replace('/[^a-zA-Z0-9_.]/', ' ', $search);


        if ($this->container->getParameter('rz_search.engine.solr.enabled')) {
            $client = $this->container->get(sprintf('solarium.client.%s', $type));
            // get a select query instance
            $query = $client->createSelect();

            // get highlighting component and apply settings
            $hl = $query->getHighlighting();
            $hl->setFields('*');
            $hl->setSimplePrefix('<span class="label label-success">');
            $hl->setSimplePostfix('</span>');
            $query->setQuery(sprintf('text:%s',$search));

            // set start and rows param (comparable to SQL limit) using fluent interface

            $adapter = new SolariumAdapter($client, $query);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage($this->container->getParameter('rz_search.settings.search.pagination_per_page') ?: 5);
            $page = $request->query->get('page') ? $request->query->get('page') : 1;
            $pager->setCurrentPage($page, false, true);
            $template = $configManager->getSearchTemplate($type, 'solr') ?:'RzSearchBundle::solr_results.html.twig';
            $response = $this->render($template, array('pager'=>$pager, 'type'=>$type));

        }

        return $response;
    }
}
