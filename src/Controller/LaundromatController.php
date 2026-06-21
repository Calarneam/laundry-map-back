<?php

namespace App\Controller;

use App\Entity\Laundromat;
use App\Repository\LaundromatRepository;
use App\Service\LaundromatNearbySerializer;
use App\Service\LaundromatSerializer;
use App\Service\WiLineApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/laundromat', name: 'api_laundromat_')]
class LaundromatController extends AbstractApiController
{
    /** Précision décimale pour regrouper les requêtes carte (~110 m par 0,001° à nos latitudes). */
    private const BOUNDING_BOX_CACHE_DECIMAL_PLACES = 3;

    public function __construct(
        private readonly LaundromatRepository $laundromatRepository,
        private readonly LaundromatNearbySerializer $laundromatNearbySerializer,
        private readonly LaundromatSerializer $laundromatSerializer,
        private readonly CacheInterface $cache,
        private readonly WiLineApiService $wiLineApiService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $southWestLatitude = $request->query->get('southWestLatitude');
        $southWestLongitude = $request->query->get('southWestLongitude');
        $northEastLatitude = $request->query->get('northEastLatitude');
        $northEastLongitude = $request->query->get('northEastLongitude');

        if (
            $southWestLatitude === null || $southWestLatitude === ''
            || $southWestLongitude === null || $southWestLongitude === ''
            || $northEastLatitude === null || $northEastLatitude === ''
            || $northEastLongitude === null || $northEastLongitude === ''
        ) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $limit = max(1, (int) $request->query->get('limit', 50));

        $filters = array_intersect_key(
            $request->query->all(),
            array_flip(['query', 'address', 'services', 'paymentMethods', 'equipmentTypes', 'openNow']),
        );

        $boundingBox = $this->snapBoundingBoxForCache(
            (float) $southWestLatitude,
            (float) $southWestLongitude,
            (float) $northEastLatitude,
            (float) $northEastLongitude,
        );

        $cacheKey = 'laundromat_bbox_'.hash('sha256', json_encode([
            'southWestLatitude' => $boundingBox['southWestLatitude'],
            'southWestLongitude' => $boundingBox['southWestLongitude'],
            'northEastLatitude' => $boundingBox['northEastLatitude'],
            'northEastLongitude' => $boundingBox['northEastLongitude'],
            'limit' => $limit,
            'filters' => $filters,
        ], JSON_THROW_ON_ERROR));

        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($boundingBox, $limit, $filters): array {
            $item->expiresAfter(LaundromatNearbySerializer::CACHE_TTL_SECONDS);
            $rows = $this->laundromatRepository->findInBoundingBox(
                $boundingBox['southWestLatitude'],
                $boundingBox['southWestLongitude'],
                $boundingBox['northEastLatitude'],
                $boundingBox['northEastLongitude'],
                $limit,
                $filters,
            );

            return $this->laundromatNearbySerializer->serializeRows($rows);
        });

        $response = $this->json($data, Response::HTTP_OK);
        $response->setPublic();
        $response->headers->set(
            'Cache-Control',
            sprintf('public, max-age=%d, s-maxage=%d', LaundromatNearbySerializer::CACHE_TTL_SECONDS, LaundromatNearbySerializer::CACHE_TTL_SECONDS),
        );

        return $response;
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $laundromat = $this->laundromatRepository->findWithDetails($id);

        if (!$laundromat instanceof Laundromat) {
            return $this->json(['error' => 'api.messages.laundromat_not_found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->laundromatSerializer->serializeDetail($laundromat);
        $data['isWiLineSynced'] = false;

        $wiLineReference = $laundromat->getWiLineReference();
        if ($wiLineReference !== null && $wiLineReference !== '') {
            try {
                $wiLineDetails = $this->wiLineApiService->getLaundryDetails($wiLineReference);
                if ($wiLineDetails !== []) {
                    $this->mergeWiLineDetails($data, $wiLineDetails);
                }
            } catch (\Throwable $exception) {
                $this->logger->warning('Wi-Line sync failed for laundromat {id}: {message}', [
                    'id' => $id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $this->json($data, Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $wiLineDetails
     */
    private function mergeWiLineDetails(array &$data, array $wiLineDetails): void
    {
        if (!empty($wiLineDetails['name'])) {
            $data['establishmentName'] = $wiLineDetails['name'];
        }

        if (!empty($wiLineDetails['address']) || !empty($wiLineDetails['city'])) {
            $data['address'] = [
                'fullAddress' => trim(sprintf(
                    '%s, %s %s, %s',
                    $wiLineDetails['address'] ?? '',
                    $wiLineDetails['postal_code'] ?? '',
                    $wiLineDetails['city'] ?? '',
                    $wiLineDetails['country'] ?? ''
                ), ', '),
                'street' => $wiLineDetails['address'] ?? null,
                'zipCode' => isset($wiLineDetails['postal_code']) ? (int) $wiLineDetails['postal_code'] : null,
                'city' => $wiLineDetails['city'] ?? null,
                'country' => $wiLineDetails['country'] ?? null,
            ];
        }

        if (!empty($wiLineDetails['phone'])) {
            $data['phone'] = $wiLineDetails['phone'];
        } elseif (!empty($data['contactPhone'])) {
            $data['phone'] = $data['contactPhone'];
        }
        if (!empty($wiLineDetails['logo'])) {
            $data['logoUrl'] = $wiLineDetails['logo'];
        }

        $paymentMethods = [];
        foreach (['coin', 'bill', 'card', 'fidelity'] as $paymentMethodKey) {
            if (!empty($wiLineDetails["{$paymentMethodKey}_accepted"])) {
                $paymentMethods[] = $paymentMethodKey;
            }
        }
        if ($paymentMethods !== []) {
            $data['paymentMethods'] = $paymentMethods;
        }

        if (!empty($wiLineDetails['opening_hours']) && \is_array($wiLineDetails['opening_hours'])) {
            $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $hours = [];
            foreach ($wiLineDetails['opening_hours'] as $day => $ranges) {
                $normalizedDay = strtolower((string) $day);
                if (!\in_array($normalizedDay, $validDays, true) || !\is_array($ranges)) {
                    continue;
                }
                foreach ($ranges as $range) {
                    if (!isset($range['open'], $range['close'])) {
                        continue;
                    }
                    $hours[] = [
                        'day' => $normalizedDay,
                        'startTime' => substr((string) $range['open'], 0, 5),
                        'endTime' => substr((string) $range['close'], 0, 5),
                    ];
                }
            }
            $data['openingHours'] = $hours;
        }

        if (!empty($wiLineDetails['machines']) && \is_array($wiLineDetails['machines'])) {
            $equipments = [];
            foreach ($wiLineDetails['machines'] as $machine) {
                $category = $machine['category_text'] ?? null;
                if (!\in_array($category, ['WASH', 'DRY'], true)) {
                    continue;
                }

                $capacity = null;
                if (preg_match('/(\d+)\s*kg/i', (string) ($machine['type_name'] ?? ''), $matches)) {
                    $capacity = (int) $matches[1];
                }

                $equipments[] = [
                    'machineId' => $machine['machine_id'] ?? null,
                    'machineNumber' => $machine['machine_number'] ?? null,
                    'type' => $category === 'WASH' ? 'washer' : 'dryer',
                    'name' => $machine['type_name'] ?? null,
                    'capacity' => $capacity,
                    'price' => isset($machine['price']) ? number_format(((int) $machine['price']) / 100, 2, '.', '') : null,
                    'reducedPrice' => isset($machine['reduced_price']) ? number_format(((int) $machine['reduced_price']) / 100, 2, '.', '') : null,
                    'duration' => $machine['duration'] ?? null,
                    'outOfOrder' => (bool) ($machine['out_of_order'] ?? false),
                ];
            }
            $data['equipments'] = $equipments;
        }

        $data['isWiLineSynced'] = true;
    }

    /**
     * Agrandit légèrement la box sur une grille fixe pour mutualiser le cache entre pans/zoom proches.
     *
     * @return array{
     *     southWestLatitude: float,
     *     southWestLongitude: float,
     *     northEastLatitude: float,
     *     northEastLongitude: float
     * }
     */
    private function snapBoundingBoxForCache(
        float $southWestLatitude,
        float $southWestLongitude,
        float $northEastLatitude,
        float $northEastLongitude,
    ): array {
        $factor = 10 ** self::BOUNDING_BOX_CACHE_DECIMAL_PLACES;

        return [
            'southWestLatitude' => floor($southWestLatitude * $factor) / $factor,
            'southWestLongitude' => floor($southWestLongitude * $factor) / $factor,
            'northEastLatitude' => ceil($northEastLatitude * $factor) / $factor,
            'northEastLongitude' => ceil($northEastLongitude * $factor) / $factor,
        ];
    }
}
