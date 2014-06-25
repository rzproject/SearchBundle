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

        if ( $search === '') {
            $response = $this->render('RzSearchBundle::empty.html.twig');
            return $response;
        }

        if ($search === NULL) {
            $response = $this->render('RzSearchBundle::no_results.html.twig');
            return $response;
        }


        if ($this->container->getParameter('rz_search.engine.solr.enabled')) {
            $client = $this->container->get('solarium.client.default');
            // get a select query instance
            $query = $client->createSelect();

            // get highlighting component and apply settings
            $hl = $query->getHighlighting();
            $hl->setFields('*');
            $hl->setSimplePrefix('<span class="label label-success">');
            $hl->setSimplePostfix('</span>');

            // set a query (all prices starting from 12)
            $query->setQuery(sprintf('text:%s',$search));

            // set start and rows param (comparable to SQL limit) using fluent interface
            $adapter = new SolariumAdapter($client, $query);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage(10);
            $page = $request->query->get('page') ? $request->query->get('page') : 1;
            $pager->setCurrentPage($page, false, true);
            $response = $this->render('RzSearchBundle::solr_results.html.twig', array('pager'=>$pager));

        } elseif ($this->container->getParameter('rz_search.engine.zend_lucene.enabled')) {
            $client = $this->container->get('rz_search.zend_lucene')->getIndex('application.sonata.newsbundle.entity.post');

            $result =$client->find($search);
            $nbResults = count($result);
            //$paginated = array_chunk($result, 2);

            if ($result) {
                $adapter = new ArrayAdapter($result);
                $pager = new Pagerfanta($adapter);
                $pager->setMaxPerPage(1);
                $page = $request->query->get('page') ? $request->query->get('page') : 1;
                $pager->setCurrentPage($page, false, true);
                $response = $this->render('RzSearchBundle::lucene_results.html.twig', array('pager'=>$pager));
            } else {
                $response = $this->render('RzSearchBundle::no_results.html.twig');
            }
        }




        return $response;
    }
}
