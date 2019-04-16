<?php
/**
 * Created by PhpStorm.
 * User: ThanhDT
 * Date: 10/18/2018
 * Time: 9:49 AM
 */

namespace Mobile\Controller\Post;

use Mobile\Controller\BaseController;
use App\Entity\PostPublishes;
use App\Service\DataExchange;
use App\Utils\Constants;
use App\Service\CryptUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxController extends BaseController
{

    /**
     * author: TrieuNT
     * create date: 2018-11-16 02:21 PM
     */
    const code_base64 = "R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=";


    /**
     * @author: TrieuNT
     * @return bool
     */
    public function is_bot_detected()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function updateView(Request $request)
    {
        if (!($this->is_bot_detected())) {
            $type = (int)$request->get('type');
            $postId = (int)$request->get('id');
            if ($type && $postId) {
                switch ($type) {
                    case 2:
                        /* update view quiz */
                        $keyQueue = Constants::QUIZ_KEY_QUEUE;
                        break;
                    case 5:
                    case 6:
                        if ($type == 5) {
                            $cookieName = Constants::COOKIE_NAME . $postId;
                            if (isset($_COOKIE[$cookieName])) {
                                return false; //new JsonResponse(false);
                            }
                        }
                        /*update share fb */
                        $keyQueue = Constants::FACEBOOK_VIEW_KEY_QUEUE;
                        break;
                    default:
                        /* update view article */
                        $keyQueue = Constants::ARTICLE_VIEW_QUEUE;
                        break;
                }

                $redis = $this->getCacheProvider(Constants::SERVER_CACHE_UPDATE_VIEW);
                if ($type == 5 || $type == 6) {
                    // do something
                } else {
                    $redis->lpush($keyQueue, $postId . '|' . time());
                }

                if ($type == 5 && isset($cookieName) && !empty($cookieName)) { //share fb set cookie
                    setcookie($cookieName, $postId, time() + 604800, "/"); //cookie 7 ngay
                }
            }
        }
        $code_binary = base64_decode(self::code_base64);
        $image = imagecreatefromstring($code_binary);
        imagegif($image);
        imagedestroy($image);
        $response = new Response();
        $response->headers->set('Content-Type', 'image/gif');
        return $response;
    }

    /**
     * Get lastest posts
     * author: ThanhDT
     * date:   2018-10-23 04:12 PM
     * @param Request $request
     * @param DataExchange $exchangeService
     * @return JsonResponse
     */
    public function getLastestPosts(Request $request, DataExchange $exchangeService, CryptUtils $cryptUtils)
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

        $postId = $curLastInfo['lastPostId'];
        $timestamp = $curLastInfo['lastPublishedTimestamp'];
        $nextPage = $curLastInfo['nextPage'];
        $cacheService = $this->getCacheProvider(Constants::SERVER_CACHE_ARTICLE);
        // Get page 1 lastest posts
        $lastestCacheKey = $this->formatCacheKey(Constants::CACHE_HOMEPAGE_LASTEST_TIMESTAMP, $postId, $timestamp);
        if (($lastestPostInfo = $cacheService->get($lastestCacheKey)) === false) {
            $em = $this->getDoctrine()->getManager();
            $sourcePosts = $em->getRepository(PostPublishes::class)
                ->getLastestPosts(Constants::HOMEPAGE_LASTEST_AJAX_LIMIT + 1, $timestamp, [$postId]);
            if ($sourcePosts) {
                if (count($sourcePosts) > Constants::HOMEPAGE_LASTEST_AJAX_LIMIT) {
                    $sourcePosts = array_slice($sourcePosts, 0, Constants::HOMEPAGE_LASTEST_AJAX_LIMIT);
                    $lastestStream = $sourcePosts[Constants::HOMEPAGE_LASTEST_AJAX_LIMIT - 1];
                    $lastInfo = [
                        'lastPostId' => $lastestStream['postId'],
                        'lastPublishedTimestamp' => $lastestStream['publishedTimestamp'],
                        'nextPage' => $nextPage + 1
                    ];
                    $lastInfo = $this->encrypt($cryptUtils, $lastInfo);
                } else {
                    $lastInfo = null;
                }
                $sourcePosts = $exchangeService->exchangeArrayArticle(
                    $sourcePosts,
                    Constants::MOBILE_IMAGE_HOME_LATEST_NEWS
                );

                $lastestPostInfo = [
                    'lastestPosts' => $sourcePosts,
                    'loadMoreToken' => $lastInfo
                ];
            } else {
                $lastestPostInfo = [
                    'lastestPosts' => [],
                    'loadMoreToken' => null
                ];
            }
            $cacheService->set($lastestCacheKey, $lastestPostInfo, $this->getParameter('cache_time')['hour']);
        }

        $data = [
            'success' => 1,
            //'showMore' => $lastestPostInfo['loadMoreToken'] ? 1 : 0,
            'loadMoreToken' => $lastestPostInfo['loadMoreToken'],
            'data' => $this->renderView('post/widget/lastest-posts.html.twig', ['lastestPosts' => $lastestPostInfo['lastestPosts']])
        ];

        return new JsonResponse($data);
    }
}
