<?php

namespace Rz\SearchBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\SolariumAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HelperController extends AbstractController
{
    protected $router;

    protected function init()
    {
        parent::init();
        $this->router = $this->get('router');
    }


    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return RedirectResponse
     */
    public function fetchDataAction(Request $request)
    {
        $search = $request->query->get($this->getQueryVar()) ?: null;
        $configKey = $request->query->get('type');

        if (!$request->isXmlHttpRequest()) {
            $url = $this->getRouter()->generate('rz_search_default', array(), UrlGeneratorInterface::ABSOLUTE_URL);
            return new RedirectResponse($url);
        }

        $page = (int) $request->query->get('page') ?: 1;

        if (!$configKey || $configKey === '') {
            return new JsonResponse(array('status' => 'KO', 'message' => $this->getTranslator()->trans('rz_search.controller.error.invalid_type', array(),  'RzSearchBundle')));
        }

        if ($search === '') {
            return new JsonResponse(array('status' => 'KO', 'message' => $this->getTranslator()->trans('rz_search.controller.error.no_search_query', array(),  'RzSearchBundle')));
        }

        if ($search === null) {
            return new JsonResponse(array('status' => 'KO', 'message' => $this->getTranslator()->trans('rz_search.controller.error.no_search_result', array(),  'RzSearchBundle')));
        }

        $search =  preg_replace('/[^a-zA-Z0-9_.]/', ' ', $search);
        $configKey =    preg_replace('/[^a-zA-Z0-9_.]/', ' ', $configKey);


        if ($this->getIsIndexEngineEnabled()) {
            $searchClient = $this->getSearchClient($configKey);

            if (!$searchClient) {
                return new JsonResponse(array('status' => 'KO', 'message' => $this->getTranslator()->trans('rz_search.controller.error.invalid_type', array(),  'RzSearchBundle')));
            }

            // get a select query instance
            $query = $searchClient->createSelect();
            // get highlighting component and apply settings
            $hl = $query->getHighlighting();
            $hl->setFields('*');
            $hl->setSimplePrefix('<span class="label label-success">');
            $hl->setSimplePostfix('</span>');
            $query->setQuery(sprintf('text:%s', $search));

            // set start and rows param (comparable to SQL limit) using fluent interface

            $adapter = new SolariumAdapter($searchClient, $query);
            $pager = new Pagerfanta($adapter);
            $pager->setMaxPerPage($this->getPerPage());
            $pager->setCurrentPage($page, false, true);
            $template = $this->getConfigManager()->getResultAjaxTemplate($configKey) ?: $this->getDefaultTemplate('results_ajax');

            $content = $this->render($template, array('pager'      =>$pager,
                                                      'type'       =>$configKey));

            $url = null;
            if ($pager->haveToPaginate() && $pager->hasNextPage()) {
                $url = $this->getRouter()->generate('rz_search_helper_fetch_data',
                                                    array('type'=>$configKey, 'page'=>$pager->getNextPage(), $this->getQueryVar()=>$search),
                                                    UrlGeneratorInterface::ABSOLUTE_URL);
            }


            return new JsonResponse(array('status' => 'OK', 'content' => $content->getContent(), 'url'=>$url));
        }

        return new JsonResponse(array('status' => 'KO', 'message' => $this->getTranslator()->trans('rz_search.controller.error.no_search_result', array(),  'RzSearchBundle')));
    }

    /**
     * @return mixed
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param mixed $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }
}
