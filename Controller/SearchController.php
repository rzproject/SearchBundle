<?php

namespace Rz\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

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

        if (NULL !== $search) {
            $config = $this->get('rz_search.config_manager');

            //var_dump($config);
            $client = $this->get('solarium.client');

            // get a select query instance
            $query = $client->createSelect();

            // get highlighting component and apply settings
            $hl = $query->getHighlighting();
            $hl->setFields('content, description, title');
            $hl->setSimplePrefix('<b>');
            $hl->setSimplePostfix('</b>');

            // set a query (all prices starting from 12)
            //$query->setQuery($search);
            $query->setQuery(sprintf('text:%s',$search));

            // set start and rows param (comparable to SQL limit) using fluent interface
            //$query->setStart(2)->setRows(20);

            // this executes the query and returns the result
            $resultset = $client->select($query);
            $highlighting = $resultset->getHighlighting();

            $response = $this->render('RzSearchBundle::results.html.twig', array('resultset'=>$resultset, 'highlighting'=>$highlighting));
            return $response;
        } else {
            $response = $this->render('RzSearchBundle::no_results.html.twig');
            return $response;
        }
    }
}
