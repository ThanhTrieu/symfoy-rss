<?php
/**
 * Created by PhpStorm.
 * User: ThanhDT
 * Date: 10/30/2018
 * Time: 1:46 PM
 */

namespace Mobile\Controller;

use App\Entity\Pages;
use App\Entity\PostPublishes;
use App\Entity\Videos;
use App\Service\Category;
use App\Service\DataExchange;
use App\Utils\Constants;
use App\Utils\Lib;
use App\Service\CryptUtils;
use Symfony\Component\HttpFoundation\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class DefaultController extends BaseController
{
    const MOBILE_TYPE_VIDEOS_HOME = 1;

    /**
     * Home page action
     * author: ThanhDT
     * date:   2018-10-17 09:23 PM
     * @param DataExchange $exchangeService
     * @param Category $category
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function index(DataExchange $exchangeService, Category $category, Request $request, CryptUtils $cryptUtils)
    {
        // Get top focus posts
        $cacheService = $this->getCacheProvider(Constants::SERVER_CACHE_ARTICLE);
        $focusCacheKey = $this->formatCacheKey(Constants::CACHE_HOMEPAGE_FOCUS);
        $dataTopFocus = [];
        if (($focusPosts = $cacheService->get($focusCacheKey)) === false) {
            $em = $this->getDoctrine()->getManager();
            $sourcePosts = $em->getRepository(PostPublishes::class)->getTopFocusPosts(Constants::FOCUS_HOME, Constants::HOMEPAGE_FOCUS_POST_LIMIT);
            $dataTopFocus = $sourcePosts;
            if ($sourcePosts) {
                $focusPosts = $exchangeService->exchangeSitemapArrayArticle($sourcePosts, Constants::MOBILE_IMAGE_HOME_TOPIC);
                $cacheService->set($focusCacheKey, $focusPosts, $this->getParameter('cache_time')['hour']);
            } else {
                $focusPosts = [];
            }
        }

        // Get page 1 lastest posts
        $currentPage = 1;
        $lastestCacheKey = $this->formatCacheKey(Constants::CACHE_HOMEPAGE_LASTEST_PAGE, Constants::HOMEPAGE_LASTEST_PAGE_INDEX);
        if (($lastestPostInfo = $cacheService->get($lastestCacheKey)) === false) {
            $ignoreTopFocusPostIds = [];
            if($dataTopFocus){
                foreach ($dataTopFocus as $key => $item){
                    $ignoreTopFocusPostIds[] = $item['postId'];
                }
            }
            $em = $this->getDoctrine()->getManager();
            $sourcePosts = $em->getRepository(PostPublishes::class)
                ->getLastestPosts(Constants::HOMEPAGE_LASTEST_LIMIT + 1, time(), $ignoreTopFocusPostIds);
            if ($sourcePosts) {
                if (count($sourcePosts) > Constants::HOMEPAGE_LASTEST_LIMIT) {
                    $sourcePosts = array_slice($sourcePosts, 0, Constants::HOMEPAGE_LASTEST_LIMIT);
                    $lastHomeStream = $sourcePosts[Constants::HOMEPAGE_LASTEST_LIMIT - 1];
                    $lastInfo = [
                        'lastPostId' => $lastHomeStream['postId'],
                        'lastPublishedTimestamp' => $lastHomeStream['publishedTimestamp'],
                        'nextPage' => $currentPage + 1
                    ];
                } else {
                    $lastInfo = null;
                }
                $sourcePosts = $exchangeService->ExchangeArrayArticle($sourcePosts, Constants::MOBILE_IMAGE_HOME_LATEST_NEWS);
                $lastestPostInfo = [
                    'lastestPosts' => $sourcePosts,
                    'loadMoreToken' => $this->encrypt($cryptUtils, $lastInfo)
                ];
            } else {
                $lastestPostInfo = [
                    'lastestPosts' => [],
                    'loadMoreToken' => null
                ];
            }
            $cacheService->set($lastestCacheKey, $lastestPostInfo, $this->getParameter('cache_time')['hour']);
        }

        // Get MostView
        $mostViewCacheKey = $this->formatCacheKey(Constants::CACHE_HOME_MOST_VIEW);
        if (($mostViewPosts = $cacheService->get($mostViewCacheKey)) === false) {
            $em = $this->getDoctrine()->getManager();
            $sourcePosts = $em->getRepository(PostPublishes::class)
                ->getMostViewPosts(Constants::HOME_MOST_VIEW_POST_LIMIT, Constants::HOME_MOST_VIEW_LAST_DAY);
            if ($sourcePosts) {
                $mostViewPosts = $exchangeService->exchangeArrayArticle($sourcePosts);
                $cacheService->set($mostViewCacheKey, $mostViewPosts, $this->getParameter('cache_time')['hour']);
            } else {
                $mostViewPosts = [];
            }
        }
    
        // Box Featured stories
        $featuredStoriesCate = $category->getCateById(Constants::FEATURED_STORIES_CATE_ID);
        $featuredStoriesCate['url'] = $this->generateUrl('news_cate', ['cateSlug' => $featuredStoriesCate['slug']]);
        $featuredStoriesPosts = self::getPostInCate(
            //$ignoreLastestIds,
            $cacheService,
            $exchangeService,
            Constants::FEATURED_STORIES_CATE_ID,
            Constants::HOME_FEATURED_POSTS_LIMIT,
            Constants::POST_AVATAR_LIST_SIZE
        );
        // Box Cars review
        $carsReviewCate = $category->getCateById(Constants::CARS_REVIEW_CATE_ID);
        $carsReviewCate['url'] = $this->generateUrl('news_cate', ['cateSlug' => $carsReviewCate['slug']]);
        $carsReviewPosts = self::getPostInCate(
            //$ignoreLastestIds,
            $cacheService,
            $exchangeService,
            Constants::CARS_REVIEW_CATE_ID,
            Constants::MOBILE_HOME_OTHER_CATE_POSTS_LIMIT,
            Constants::MOBILE_IMAGE_HOME_FEATURED_STORIES,
            Constants::MOBILE_IMAGE_HOME_LATEST_NEWS
        );
        // Box Bikes review
        $bikesReviewCate = $category->getCateById(Constants::BIKES_REVIEW_CATE_ID);
        $bikesReviewCate['url'] = $this->generateUrl('news_cate', ['cateSlug' => $bikesReviewCate['slug']]);
        $bikesReviewPosts = self::getPostInCate(
            //$ignoreLastestIds,
            $cacheService,
            $exchangeService,
            Constants::BIKES_REVIEW_CATE_ID,
            Constants::MOBILE_HOME_OTHER_CATE_POSTS_LIMIT,
            Constants::MOBILE_IMAGE_HOME_FEATURED_STORIES,
            Constants::MOBILE_IMAGE_HOME_LATEST_NEWS
        );

        // TrieuNT added - Box Videos
        $videosViewCacheKey = $this->formatCacheKey(Constants::CACHE_HOME_VIDEOS_VIEW);

        if (($videosViewPosts = $cacheService->get($videosViewCacheKey)) === false) {
            $em = $this->getDoctrine()->getManager();
            $sourceVideos = $em->getRepository(Videos::class)
                ->getMostViewVideos(Constants::START_PAGE, Constants::HOME_OTHER_CATE_POSTS_LIMIT);
            if ($sourceVideos) {
                $mostViewVideos = $exchangeService->exchangeArrayVideosGallery($sourceVideos, Constants::POST_AVATAR_HOME_CATE_SIZE, self::MOBILE_TYPE_VIDEOS_HOME);
                $videosViewPosts['mostViewVideos'] = $mostViewVideos;
            } else {
                $videosViewPosts['mostViewVideos'] = [];
            }
            $cacheService->set($videosViewCacheKey, $videosViewPosts, $this->getParameter('cache_time')['hour']);
        }

        // Build Seo
        $indexUrl = $this->generateUrl('index');
        $seo = $this->buildPagingMeta($indexUrl, $this->getParameter('home_title'), 1, 1, $this->getParameter('site_name'));
        $seo = array_merge($seo, array(
            'og_type' => 'object',
            'is_home' => true,
            'description' => $this->getParameter('site_desc'),
            'amp' => $this->getParameter('mobile')
        ));

        $response = $this->render('default/index.html.twig', [
            'focusPosts' => $focusPosts,
            'lastestPosts' =>  $lastestPostInfo['lastestPosts'],
            'loadMoreToken' => $lastestPostInfo['loadMoreToken'],
            'mostViewPosts' => $mostViewPosts,
            'featuredStoriesPosts' => $featuredStoriesPosts,
            'carsReviewPosts' => $carsReviewPosts,
            'bikesReviewPosts' => $bikesReviewPosts,
            'featuredStoriesCate' => $featuredStoriesCate,
            'carsReviewCate' => $carsReviewCate,
            'bikesReviewCate' => $bikesReviewCate,
            'parentSlug' => 'home',
            'mostViewVideos' => $videosViewPosts['mostViewVideos'],
            'seo'=>$seo,
        ]);
        // ThanhDT: Set cache page
        $this->addCachePage($request, $response);

        return $response;
    }

    /**
     * Get top posts in category
     * author: ThanhDT
     * date:   2018-10-19 05:22 PM
     * @param $ignoreLastestIds
     * @param $cacheService
     * @param DataExchange $exchangeService
     * @param $cateId
     * @param $limit
     * @param null $imageSize
     * @param null $specialSize
     * @return array
     * @throws \Exception
     */
    private function getPostInCate($cacheService, DataExchange $exchangeService, $cateId, $limit, $imageSize = null, $specialSize = null)
    {
        $cateCacheKey = $this->formatCacheKey(Constants::CACHE_HOMEPAGE_CATE_LIST, $cateId);
        if (($catePosts = $cacheService->get($cateCacheKey)) === false) {
            $em = $this->getDoctrine()->getManager();
            $sourcePosts = $em->getRepository(PostPublishes::class)->getTopPostsInCate($cateId, $limit);
            if ($sourcePosts) {
                $catePosts = $exchangeService->exchangeArrayArticle($sourcePosts, $imageSize, $specialSize);
                $cacheService->set($cateCacheKey, $catePosts, $this->getParameter('cache_time')['hour']);
            }
        }

        return $catePosts;
    }


    /**
     * about page
     * author: TrieuNT
     * create date: 2018-11-14 03:17 PM
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function about()
    {
        $url = $this->generateUrl('about');
        $seo = $this->buildPagingMeta($url, Constants::SEO_TITLE_ABOUT, 1, 1, $this->getParameter('site_name'));
        $seo = array_merge($seo, array(
            'og_type' => 'object',
            'is_home' => false
        ));
        return $this->render('default/about.html.twig',['seo'=>$seo]);
    }

    /**
     * privacy-policy page
     * author: TrieuNT
     * create date: 2018-11-14 03:26 PM
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function privacyPolicy()
    {
        $slug = Constants::SLUG_PRIVACY_POLICY;
        $serviceCache = $this->getCacheProvider(Constants::SERVER_CACHE_ARTICLE);
        $cateCacheKey = $this->formatCacheKey(Constants::CACHE_HOMEPAGE_PRIVACY_POLICY);
        if (($dataPages = $serviceCache->get($cateCacheKey)) === false) {
            $em = $this->getDoctrine()->getManager();
            $sourcePages = $em->getRepository(Pages::class)->getDetailPage($slug);
            if ($sourcePages) {
                $dataPages = $sourcePages;
                $serviceCache->set($cateCacheKey, $dataPages, $this->getParameter('cache_time')['hour']);
            } else {
                $dataPages = [];
            }
        }
        $url = $this->generateUrl('privacy_policy');
        $seo = $this->buildPagingMeta($url, Constants::SEO_TITLE_PRIVACY_POLICY, 1, 1, $this->getParameter('site_name'));
        $seo = array_merge($seo, array(
            'og_type' => 'object',
            'is_home' => false
        ));
        return $this->render('default/privacy-policy.html.twig',[
            'seo'=>$seo,
            'sourcePages' => $dataPages
        ]);
    }

    /**
     * contact page
     * author: TrieuNT
     * create date: 2018-11-14 03:29 PM
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contact(Request $request)
    {
        $url = $this->generateUrl('contact');
        $seo = $this->buildPagingMeta($url, Constants::SEO_TITLE_CONTACT, 1, 1, $this->getParameter('site_name'));
        $seo = array_merge($seo, array(
            'og_type' => 'object',
            'is_home' => false
        ));

        $message = $request->get('state');
        return $this->render('default/contact.html.twig', ['message' => $message,'seo'=>$seo]);
    }


    /**
     * contactSendmail
     * author: TrieuNT
     * create date: 2018-11-14 04:30 PM
     * @param Request $request
     * @return mixed
     */
    public function contactSendmail(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $fullName = $request->get('txtName');
            $fullName = filter_var($fullName, FILTER_SANITIZE_STRING);

            $phoneNumber = $request->get('txtPhone');
            $phoneNumber = filter_var($phoneNumber, FILTER_SANITIZE_STRING);

            $email = $request->get('txtEmail');
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);

            $questions = $request->get('tctQuestion');
            $questions = filter_var($questions, FILTER_SANITIZE_STRING);

            $message = $this->renderView(
                'default/contact-sendmail.html.twig',
                [
                    'name' => $fullName,
                    'phone' => $phoneNumber,
                    'email' => $email,
                    'questions' => $questions
                ]
            );

            $mail = new PHPMailer(true);
            try {
                //Server settings
                //$mail->SMTPDebug = 2;
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'noreply@indianautosblog.com';
                $mail->Password = 'ADFKw3k23o4i2432@#$%';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                //Recipients
                $mail->setFrom('noreply@indianautosblog.com', 'IndianAutosBlog');
                $mail->addAddress(Constants::EMAIL_SUPPORT_CUSTOMER);

                //Content
                $mail->isHTML(true);
                $mail->MsgHTML($message);
                $mail->Subject = 'The questions of customer from indianautosblog.com';
                $mail->CharSet = 'utf-8';

                $mail->send();
                return $this->redirectToRoute('contact', ['state'=>'success']);
            } catch (\Exception $e) {
                var_dump(' error setting '.$mail->ErrorInfo);
            }
        }
    }

    public function _404page()
    {
        $seo = array(
            'title' => 'Page not found - ' . $this->getParameter('site_name'),
            'og_type' => 'object',
            'is_home' => false
        );
        $response = $this->render('404.html.twig', array('seo' => $seo));
        $response->setStatusCode(404);
        return $response;
    }

    /**
     * Show exception 404 page
     * author: ThanhDT
     * date:   2018-12-19 04:56 PM
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showException(){
        $seo = array(
            'title' => 'Page not found - ' . $this->getParameter('site_name'),
            'og_type' => 'object',
            'is_home' => false
        );
        $response = $this->render('404.html.twig', array('seo' => $seo));
        $response->setStatusCode(404);
        return $response;
    }
}
