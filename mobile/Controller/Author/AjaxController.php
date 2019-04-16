<?php
/**
 * Created by PhpStorm.
 * User: ThanhDT
 * Date: 10/18/2018
 * Time: 9:50 AM
 */

namespace Mobile\Controller\Author;

use Mobile\Controller\BaseController;
use App\Entity\PostPublishes;
use App\Service\DataExchange;
use App\Utils\Constants;
use App\Service\CryptUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends BaseController
{
    /**
     * ajax List post focus Author
     * author: AnhPT4
     * date:   2018-10-23 10:50 AM
     * @param $tagSlug
     * @param int $currentPage
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function ajax(Request $request, DataExchange $dataExchangeService, CryptUtils $cryptUtils)
    {
        $token = $request->query->get('loadMoreToken');
        if (!$token) {
            return new JsonResponse([
                'success' => 0
            ]);
        }
        $curLastInfo = $this->decrypt($cryptUtils, $token);
        if (!$curLastInfo) {
            return new JsonResponse([
                'success' => 0
            ]);
        }

        $postAuthor = $curLastInfo['authorId'];
        $postId = $curLastInfo['lastPostId'];
        $timestamp = $curLastInfo['lastPublishedTimestamp'];
        $nextPage = $curLastInfo['nextPage'];
        // Get article in category
        // Get article in category
        $articleKeyCache = $this->formatCacheKey(Constants::CACHE_AUTHOR_LASTEST_TIMESTAMP, $postAuthor, $timestamp);
        $service_cache = $this->getCacheProvider(Constants::SERVER_CACHE_ARTICLE);
        $authorPostInfo = $service_cache->get($articleKeyCache);
        if ($authorPostInfo === false) {
            $em = $this->getDoctrine()->getManager();
            $sourcePosts = $em->getRepository(PostPublishes::class)->getArticleByAuthorTimestamp($postAuthor, Constants::PAGE_SIZE + 1, $timestamp, [$postId]);
            if ($sourcePosts) {
                if (count($sourcePosts) > Constants::PAGE_SIZE) {
                    $sourcePosts = array_slice($sourcePosts, 0, Constants::PAGE_SIZE);
                    $lastestStream = $sourcePosts[Constants::PAGE_SIZE - 1];
                    $lastInfo = [
                        'authorId' => $postAuthor,
                        'lastPostId' => $lastestStream['postId'],
                        'lastPublishedTimestamp' => $lastestStream['publishedTimestamp'],
                        'nextPage' => $nextPage + 1
                    ];
                    $lastInfo = $this->encrypt($cryptUtils, $lastInfo);
                } else {
                    $lastInfo = null;
                }
                $sourcePosts = $dataExchangeService->exchangeArrayArticle($sourcePosts, Constants::MOBILE_IMAGE_HOME_LATEST_NEWS);

                $authorPostInfo = [
                    'posts' => $sourcePosts,
                    'loadMoreToken' => $lastInfo
                ];
            } else {
                $authorPostInfo = [
                    'posts' => [],
                    'loadMoreToken' => null
                ];
            }
            $service_cache->set($articleKeyCache, $authorPostInfo, $this->getParameter('cache_time')['hour']);
        }

        $data = [
            'success' => 1,
            'loadMoreToken' => $authorPostInfo['loadMoreToken'],
            'data' => $this->renderView('category/contents.html.twig', ['news_list' => $authorPostInfo['posts']])
        ];

        return new JsonResponse($data);
    }
}
