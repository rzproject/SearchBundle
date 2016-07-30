<?php

namespace Rz\SearchBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\SolariumAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SearchController extends AbstractController
{
    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return RedirectResponse
     */
    public function searchAction(Request $request)
    {
        if (!$this->getIsIndexEngineEnabled()) {
            throw $this->createNotFoundException($this->getTranslator()->trans('rz_search.controller.error.invalid_search_cleint', array(),  'RzSearchBundle'));
        }

        $query = $this->container->get('request')->query->get($this->getQueryVar()) ?: null;
        $query = $query ? preg_replace('/[^a-zA-Z0-9_.]/', ' ', $query): null;

        $form = $this->createFormBuilder()
            ->add($this->getQueryVar(),  TextType::class, array('data'=>$query))
            ->add('type',   HiddenType::class, array('data'=>$this->getDefaultIdentifier()))
            ->add('search', SubmitType::class, array('label' => 'btn.search'))
            ->getForm();

        $form->handleRequest($request);

        $params = array('form' =>$form->createView(), 'query_var'=>$this->getQueryVar());

        if ($request->getRealMethod() === Request::METHOD_GET && $query) {
            $type = $this->getDefaultIdentifier();

            $pager = $this->search($type, 1, $query);

            $template = $this->getConfigManager()->getResultTemplate($type) ?: $this->getDefaultTemplate('results');
            $params = array_merge($params, array('pager'=>$pager, 'search'=>$query, 'type'=> $type));
            $response = $this->render($template, $params);
            return $response;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = (object) $form->getData();

            $queryVar = $this->getQueryVar();

            $searchString =  preg_replace('/[^a-zA-Z0-9_.]/', ' ', $data->$queryVar);
            $type         =  preg_replace('/[^a-zA-Z0-9_.]/', ' ', $data->type) ?: $this->getDefaultIdentifier();

            if (!$searchString || $searchString === '') {
                $response = $this->render($this->getConfigManager()->getEmptyTemplate($type) ?: $this->getDefaultTemplate('empty'), $params);
                return $response;
            }

            $pager = $this->search($type, 1, $searchString);

            $template = $this->getConfigManager()->getResultTemplate($type) ?: $this->getDefaultTemplate('results');
            $params = array_merge($params, array('pager'=>$pager, 'search'=>$searchString, 'type'=> $type));
            $response = $this->render($template, $params);
            return $response;
        }

        $template = $this->getConfigManager()->getResultTemplate($this->getDefaultIdentifier()) ?: $this->getDefaultTemplate('results');
        return  $this->render($template, $params);
    }

    protected function search($type, $page = 1, $searchString = null)
    {
        $searchClient = $this->getSearchClient($type);

        if (!$searchClient) {
            throw $this->createNotFoundException($this->getTranslator()->trans('rz_search.controller.error.invalid_search_cleint', array(),  'RzSearchBundle'));
        }

        // get a select query instance
        $select = array(
            'query'         => '*:*',
            'fields'        => array('id','title','description', 'url'),
        );
        $query = $searchClient->createSelect($select);
        // get highlighting component and apply settings
        $hl = $query->getHighlighting();
        //$hl->setFields('*');
        $hl->setFields('title, description, url');
        $hl->setSimplePrefix('<span class="label label-success">');
        $hl->setSimplePostfix('</span>');
        $query->setQuery(sprintf('text:%s', $searchString));


        $resultset = $searchClient->select($query);
        $highlighting = $resultset->getHighlighting();

        $adapter = new SolariumAdapter($searchClient, $query);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($this->getPerPage());
        $pager->setCurrentPage($page, false, true);

        return $pager;
    }
}
