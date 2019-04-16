<?php
/**
 * Created by PhpStorm.
 * User: TrieuNT
 * Date: 10/30/2018
 * Time: 8:47 AM
 */

namespace Mobile\Controller;

use App\Service\Elasticsearch;
use App\Utils\Lib;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\DataExchange;

class SearchController extends BaseController
{
    const LIMIT_ITEMS = 10;

    /**
     * author: TrieuNT
     * create date: 2018-10-30 08:49 AM
     * @param Elasticsearch $elasticSearch
     * @param Request $request
     * @param DataExchange $dataExchangeService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, Elasticsearch $elasticSearch, DataExchange $dataExchangeService)
    {
        $total = 0;
        $error = '';
        $keyword = $request->get('q');
        $keyword = filter_var($keyword, FILTER_SANITIZE_STRING);

        $page = $request->get('page');
        $page = (is_numeric($page) && $page > 0) ? $page : 1;
        $start = ($page - 1) * self::LIMIT_ITEMS;
        // search for title or sapo
        $queryData = '{"multi_match" : {"query" : "'.$keyword.'", "fields": ["title", "sapo"] }}';
        $data = $elasticSearch->search(Elasticsearch::INDIA_POSTS_INDEX, $queryData, $start, self::LIMIT_ITEMS, $total, $error);
        $mainData = $dataExchangeService->exchangeArraySearchPost($data);
        $lastSearch = count($mainData) < self::LIMIT_ITEMS ? false : true;

        // Build Seo
        $searchUrl = $this->generateUrl('search').'?q='.$keyword;
        $seo = $this->buildPagingMeta($searchUrl, $keyword, $page, $page, $this->getParameter('site_name'));
        $seo = array_merge($seo, array(
            'og_type' => 'object',
            'is_home' => true,
        ));

        $response = $this->render('default/search.html.twig', [
            'data' => $mainData,
            'lastSearch' => $lastSearch,
            'totalRecord' => $total,
            'keyword' => $keyword,
            'page' => $page,
            'url_ajax' => $this->generateUrl('search_ajax'),
            'seo' => $seo,
            'module' => 'search'
        ]);

        return $response;
    }

    /**
     * author: TrieuNT
     * create date: 2018-11-08 05:01 PM
     * @param Elasticsearch $elasticSearch
     * @param Request $request
     * @param DataExchange $dataExchangeService
     * @return \Symfony\Component\HttpFoundation\Response
     */

    public function searchAjax(Request $request, Elasticsearch $elasticSearch, DataExchange $dataExchangeService)
    {
        if ($request->isXmlHttpRequest()) {
            $total = 0;
            $error = '';
            $keyword = $request->get('q');
            $keyword = filter_var($keyword, FILTER_SANITIZE_STRING);
            $page = $request->get('page');
            $page = (is_numeric($page) && $page > 0) ? $page : 1;
            $start = ($page - 1) * self::LIMIT_ITEMS;
            // search for title or sapo
            $queryData = '{"multi_match" : {"query" : "'.$keyword.'", "fields": ["title", "sapo"] }}';
            $data = $elasticSearch->search(Elasticsearch::INDIA_POSTS_INDEX, $queryData, $start, self::LIMIT_ITEMS, $total, $error);
            $mainData = $dataExchangeService->exchangeArraySearchPost($data);
            $lastSearch = count($mainData) < self::LIMIT_ITEMS ? false : true;
            $response = [
                'lastSearch' => $lastSearch,
                'module' => 'search',
                'data' => $this->renderView('default/search-ajax.html.twig', [
                    'data' => $mainData
                ])
            ];
            return new JsonResponse($response);
        }
    }

    public function changToSearch(Request $request)
    {
        $keyword = $request->get('keyword');
        $keyword = strtolower($keyword);
        $findTag = strpos($keyword, '/');

        if ($findTag !== false) {
            $key = substr($keyword, 0, $findTag);
            if (is_numeric($key)) {
                $key = preg_replace('/[^a-z-\+_]/i', '', $keyword);
            }
            $keyword = $key;
        }

        $searchUrl = $this->generateUrl('search').'?q='.$keyword;
        return $this->redirect($searchUrl, 301);
    }
}
