<?php

namespace Rz\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SearchController extends Controller
{
    /**
     * @param null $search
     *
     * @return RedirectResponse
     */
    public function searchAction($search = null)
    {

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

            // display the total number of documents found by solr
            //echo 'NumFound: '.$resultset->getNumFound();

//            // show documents using the resultset iterator
//            foreach ($resultset as $document) {
//
//                echo '<hr/><table>';
//
//                // the documents are also iterable, to get all fields
//                foreach($document AS $field => $value)
//                {
//                    // this converts multivalue fields to a comma-separated string
//                    if(is_array($value)) $value = implode(', ', $value);
//
//                    echo '<tr><th>' . $field . '</th><td>' . $value . '</td></tr>';
//                }
//
//                echo '</table>';
//            }

            $response = $this->render('RzSearchBundle::results.html.twig', array('resultset'=>$resultset, 'highlighting'=>$highlighting));
            return $response;
        }
    }
}
