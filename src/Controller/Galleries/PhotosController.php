<?php
/**
 * Created by PhpStorm.
 * User: Anhpt4
 * Date: 10/29/2018
 * Time: 5:33 PM
 */

namespace App\Controller\Galleries;

use App\Controller\BaseController;
use App\Entity\PhotoGalleries;
use App\Service\DataExchange;
use App\Utils\Constants;
use App\Utils\Lib;
use Symfony\Component\HttpFoundation\Request;

class PhotosController extends BaseController
{
    /**
     * get data PHOTOS LASTEST PAGE
     * author: AnhPT4
     * date:   2018-10-30 10:31 AM
     * @param int $currentPage
     * @param DataExchange $dataExchangeService
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function list(Request $request, DataExchange $dataExchangeService)
    {
        $currentPage = $request->get('page', 1);
        $galleryId = $request->get('galleryId', 0);
        $em = $this->getDoctrine()->getManager();
        
        // Get Photos in Post
        $key = $galleryId ? $currentPage.'-'.$galleryId:$currentPage;
        $photosKeyCache = sprintf(Constants::CACHE_PHOTOS_PAGE, $key);
        $cacheService = $this->getCacheProvider(Constants::SERVER_CACHE_ARTICLE);
        $photoGalleries = $cacheService->get($photosKeyCache);
        if ($photoGalleries === false) {
            if ($galleryId) {
                $gallery = $em->getRepository(PhotoGalleries::class)->getImageGalleryById($galleryId);
                $photoGalleries['gallery'] = $gallery;
            }
            //$em = $this->getDoctrine()->getManager();
            $galleryTotal = $em->getRepository(PhotoGalleries::class)->getPhotoGalleriesCount();
            $data = $em->getRepository(PhotoGalleries::class)->getPhotoGalleriesPaging($currentPage, Constants::PAGE_SIZE);
            if ($data) {
                $list = $dataExchangeService->exchangeArrayGallery($data);
                $photoGalleries['total_count'] = $galleryTotal;
                $totalPage = ceil($galleryTotal / Constants::PAGE_SIZE);
                $photoGalleries['total_page'] = $totalPage;
                $photoGalleries['current_page'] = $currentPage;
                $photoGalleries['list'] = $list;
            } else {
                $totalPage = 1;
                $photoGalleries['total_count'] = 0;
                $photoGalleries['total_page'] = 1;
                $photoGalleries['current_page'] = $currentPage;
                $photoGalleries['list'] = [];
            }
            $cacheService->set($photosKeyCache, $photoGalleries, $this->getParameter('cache_time')['hour']);
        }else{
            $gallery = !empty($photoGalleries['gallery'])? $photoGalleries['gallery']:[];
        }

        // Paging
        $pagination = [
            'pages_count' => $photoGalleries['total_page'],
            'current_page' => $currentPage,
            'url' => $this->generateUrl('galleries_photos'),
        ];

        // Build Seo
        if (!empty($gallery)) {
            $photoUrl = $this->generateUrl('galleries_detail_photos', ['slug' => $gallery['slug'],'galleryId'=>$galleryId]);
            $seo = $this->buildPagingMeta($photoUrl, $gallery['title'], 1, 1, $this->getParameter('site_name'));
            $description = sprintf(Constants::BUILD_FORM_SEO_META_DES_IMAGES_DETAIL,$gallery['post_title']);
        } else {
            $photoUrl = $this->generateUrl('galleries_photos');
            $seo = $this->buildPagingMeta($photoUrl, 'IMAGES', $currentPage, $photoGalleries['total_page'], $this->getParameter('site_name'));
            $description = sprintf(Constants::BUILD_FORM_SEO_META_DES_IMAGES,$currentPage);
        }
        
        $seo = array_merge($seo, array(
            'og_type' => 'object',
            'is_home' => false,
            'description' => $description
        ));

//        var_dump($photoGalleries);die;
        $response = $this->render('galleries/photos2.html.twig', array(
            'pagination' => $pagination,
            'list' => $photoGalleries['list'],
            'gallery_id' => $galleryId,
            'gallery_title' => !empty($gallery)? $gallery['title']:'',
            'gallery_url' => !empty($gallery)? $this->generateUrl('galleries_detail_photos', ['slug'=>$gallery['slug']?$gallery['slug']:'photos','galleryId'=>$gallery['galleryId']]):'',
            'gallery_title' => !empty($gallery) ? $gallery['title'] : '',
            'gallery_url' => !empty($gallery) ? $this->generateUrl('galleries_detail_photos', ['slug' => $gallery['slug'] ? $gallery['slug'] : 'photos', 'galleryId' => $gallery['galleryId']]) : '',
            'url_post' => !empty($gallery) ? $this->generateUrl('news_detail', ['slug' => $gallery['post_slug'] ? $gallery['post_slug'] : 'post', 'postId' => $gallery['postId']]) : '',
            'url_ajax_photos' => $this->generateUrl('galleries_ajax_photos'),
            'seo' => $seo,
            'parentSlug' => 'galleries'
        ));

        return $response;
    }
}
