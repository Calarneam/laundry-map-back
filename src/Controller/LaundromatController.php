<?php

namespace App\Controller;

use App\Controller\AbstractApiController;
use App\Repository\LaundromatRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/laundromat', name: 'api_laundromat_')]
class LaundromatController extends AbstractApiController
{
    public function __construct(
        private readonly LaundromatRepository $laundromatRepository,
    ) {}

    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');
        
        if (!$latitude || !$longitude) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }
        
        $radius = $request->query->get('radius', 5000);
        $limit = $request->query->get('limit', 50);

        $filters = [];
        if ($request->query->has('services')) {
            $filters['services'] = $request->query->get('services');
        }
        if ($request->query->has('paymentMethods')) {
            $filters['paymentMethods'] = $request->query->get('paymentMethods');
        }
        if ($request->query->has('equipmentTypes')) {
            $filters['equipmentTypes'] = $request->query->get('equipmentTypes');
        }

        $laundromats = $this->laundromatRepository->findNearby($latitude, $longitude, $radius, $limit, $filters);

        return $this->json($laundromats, Response::HTTP_OK);
    }

}
