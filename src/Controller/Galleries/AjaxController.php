<?php
/**
 * Created by PhpStorm.
 * User: AnhPT4
 * Date: 10/18/2018
 * Time: 9:50 AM
 */

namespace App\Controller\Galleries;

use App\Controller\BaseController;
use App\Entity\PhotoGalleriesImages;
use App\Service\DataExchange;
use App\Utils\Constants;
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
     */
    public function ajax(Request $request, DataExchange $dataExchangeService)
    {
        $galleryId = $request->get('id', 0);
        $response = [
            'success' => 0,
            'html' => '',
        ];
        
        if (!$galleryId) {
            return new JsonResponse($response);
        }
        
        // Get article in category
        $galleryKeyCache = sprintf(Constants::CACHE_PHOTOS_GALLERY_IMAGES_DATA, $galleryId);
        $serviceCache = $this->getCacheProvider(Constants::SERVER_CACHE_ARTICLE);
        $galleryExchange = $serviceCache->get($galleryKeyCache);
//        var_dump($galleryExchange);die;
        if ($galleryExchange === false) {
            $em = $this->getDoctrine()->getManager();
            $data = $em->getRepository(PhotoGalleriesImages::class)->getListAllImageByGalleryById($galleryId);
            if ($data) {
                $galleryExchange = $dataExchangeService->ExchangeAllDetailGalleryPhotoData($data, Constants::IMAGE_THUMB_POPUP, Constants::IMAGE_LARGE_POPUP);
            } else {
                $galleryExchange = [];
            }
            $serviceCache->set($galleryKeyCache, $galleryExchange, $this->getParameter('cache_time')['hour']);
        }
        
        if (count($galleryExchange)) {
            $response = [
                'success' => 1,
                'html' => $this->renderView('galleries/popup.html.twig', ['list' => $galleryExchange])
            ];
        }
        return new JsonResponse($response);
    }
}