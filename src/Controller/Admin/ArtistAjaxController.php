<?php

namespace App\Controller\Admin;

use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ArtistAjaxController extends AbstractController
{
    #[Route('/admin/api/artists/search', name: 'admin_api_artist_search')]
    public function search(Request $request, ArtistRepository $artistRepository): JsonResponse
    {
        $query = $request->query->get('q');
        
        if (empty($query)) {
             return new JsonResponse([]);
        }

        // Use the existing searchByName method or create a more specific one
        // searchByName returns entities, we need arrays
        $artists = $artistRepository->searchByName($query);
        
        $results = array_map(function($artist) {
            return [
                'id' => $artist->getId(),
                'text' => $artist->getName(),
                // 'image' => ... if we wanted images
            ];
        }, $artists);

        return new JsonResponse($results);
    }
}
