<?php

namespace App\Controller;

use App\Controller\AbstractApiController;
use App\Repository\LaundromatRepository;
use App\Service\LaundromatNearbySerializer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/laundromat', name: 'api_laundromat_')]
class LaundromatController extends AbstractApiController
{
    public function __construct(
        private readonly LaundromatRepository $laundromatRepository,
        private readonly LaundromatNearbySerializer $laundromatNearbySerializer,
    ) {}

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $swLat = $request->query->get('swLat');
        $swLng = $request->query->get('swLng');
        $neLat = $request->query->get('neLat');
        $neLng = $request->query->get('neLng');

        if ($swLat === null || $swLng === null || $neLat === null || $neLng === null) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $limit = max(1, (int) $request->query->get('limit', 50));

        $filters = array_intersect_key(
            $request->query->all(),
            array_flip(['address', 'services', 'paymentMethods', 'equipmentTypes', 'query', 'openNow']),
        );

        $rows = $this->laundromatRepository->findInBbox(
            (float) $swLat,
            (float) $swLng,
            (float) $neLat,
            (float) $neLng,
            $limit,
            $filters,
        );

        return $this->json($this->laundromatNearbySerializer->serializeRows($rows), Response::HTTP_OK);
    }
}
