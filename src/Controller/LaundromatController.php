<?php

namespace App\Controller;

use App\Controller\AbstractApiController;
use App\Entity\Laundromat;
use App\Repository\LaundromatRepository;
use App\Service\LaundromatNearbySerializer;
use App\Service\WiLineApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/laundromat', name: 'api_laundromat_')]
class LaundromatController extends AbstractApiController
{
    public function __construct(
        private readonly LaundromatRepository $laundromatRepository,
        private readonly LaundromatNearbySerializer $laundromatNearbySerializer,
        private readonly CacheInterface $cache,
        private readonly WiLineApiService $wiLineApiService,
    ) {}

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if (!$latitude || !$longitude) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;
        $radius = max(1, (int) $request->query->get('radius', 5000));
        $limit = max(1, (int) $request->query->get('limit', 50));

        $filters = array_intersect_key(
            $request->query->all(),
            array_flip(['services', 'paymentMethods', 'equipmentTypes']),
        );

        $cacheKey = 'laundromat_nearby_'.hash('sha256', (string) $request->getQueryString());

        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($lat, $lng, $radius, $limit, $filters): array {
            $item->expiresAfter(LaundromatNearbySerializer::CACHE_TTL_SECONDS);
            $rows = $this->laundromatRepository->findNearby($lat, $lng, $radius, $limit, $filters);

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

        if (!$laundromat) {
            return $this->json(['error' => 'api.messages.laundromat_not_found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializeLaundromat($laundromat);
        $data['isWiLineSynced'] = false;

        $wiLineReference = $laundromat->getWiLineReference();
        if ($wiLineReference !== null && $wiLineReference !== '') {
            try {
                $wiLineDetails = $this->wiLineApiService->getLaundryDetails($wiLineReference);
                if ($wiLineDetails !== []) {
                    $this->mergeWiLineDetails($data, $wiLineDetails);
                }
            } catch (\Throwable) {
            }
        }

        return $this->json($data, Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLaundromat(Laundromat $laundromat): array
    {
        return [
            'id' => $laundromat->getId(),
            'establishmentName' => $laundromat->getEstablishmentName(),
            'description' => $laundromat->getDescription(),
            'contactEmail' => $laundromat->getContactEmail(),
            'wiLineReference' => $laundromat->getWiLineReference(),

            'address' => $laundromat->getAddress() ? [
                'fullAddress' => $laundromat->getAddress()->getAddress(),
                'street' => $laundromat->getAddress()->getStreet(),
                'zipCode' => $laundromat->getAddress()->getZipCode(),
                'city' => $laundromat->getAddress()->getCity(),
                'country' => $laundromat->getAddress()->getCountry(),
            ] : null,

            'equipments' => array_map(fn($equipment) => [
                'type' => $equipment->getType()->value,
                'capacity' => $equipment->getCapacity(),
                'price' => $equipment->getPrice(),
                'duration' => $equipment->getDuration(),
            ], $laundromat->getEquipments()->toArray()),

            'services' => array_map(
                fn($service) => $service->getName(),
                $laundromat->getServices()->toArray()
            ),

            'openingHours' => array_map(fn($closure) => [
                'day' => $closure->getDay()->value,
                'startTime' => $closure->getStartTime()->format('H:i'),
                'endTime' => $closure->getEndTime()->format('H:i'),
            ], $laundromat->getClosures()->toArray()),

            'paymentMethods' => array_map(fn($paymentMethod) => $paymentMethod->getName(), $laundromat->getPaymentMethods()->toArray()),

            'photos' => array_map(fn($media) => [
                'id' => $media->getId(),
                'url' => $media->getLocation(),
                'name' => $media->getOriginalName(),
                'description' => $media->getDescription(),
            ], $laundromat->getMedias()->toArray()),

            'ratings' => array_map(fn($rating) => [
                'id' => $rating->getId(),
                'rating' => $rating->getRating(),
                'comment' => $rating->getComment(),
                'createdAt' => $rating->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $laundromat->getRatings()->toArray()),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $wi
     * @return array<string, mixed>
     */
    private function mergeWiLineDetails(array &$data, array $wi): void
    {
        if (!empty($wi['name'])) {
            $data['establishmentName'] = $wi['name'];
        }

        if (!empty($wi['address']) || !empty($wi['city'])) {
            $data['address'] = [
                'fullAddress' => trim(sprintf(
                    '%s, %s %s, %s',
                    $wi['address'] ?? '',
                    $wi['postal_code'] ?? '',
                    $wi['city'] ?? '',
                    $wi['country'] ?? ''
                ), ', '),
                'street' => $wi['address'] ?? null,
                'zipCode' => isset($wi['postal_code']) ? (int) $wi['postal_code'] : null,
                'city' => $wi['city'] ?? null,
                'country' => $wi['country'] ?? null,
            ];
        }

        if (!empty($wi['phone'])) {
            $data['phone'] = $wi['phone'];
        }
        if (!empty($wi['logo'])) {
            $data['logoUrl'] = $wi['logo'];
        }

        $paymentMethods = [];
        foreach (['coin', 'bill', 'card', 'fidelity'] as $pm) {
            if (!empty($wi["{$pm}_accepted"])) {
                $paymentMethods[] = $pm;
            }
        }
        if ($paymentMethods !== []) {
            $data['paymentMethods'] = $paymentMethods;
        }

        if (!empty($wi['opening_hours']) && \is_array($wi['opening_hours'])) {
            $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $hours = [];
            foreach ($wi['opening_hours'] as $day => $ranges) {
                $normalized = strtolower((string) $day);
                if (!\in_array($normalized, $validDays, true) || !\is_array($ranges)) {
                    continue;
                }
                foreach ($ranges as $range) {
                    if (!isset($range['open'], $range['close'])) {
                        continue;
                    }
                    $hours[] = [
                        'day' => $normalized,
                        'startTime' => substr((string) $range['open'], 0, 5),
                        'endTime' => substr((string) $range['close'], 0, 5),
                    ];
                }
            }
            $data['openingHours'] = $hours;
        }

        if (!empty($wi['machines']) && \is_array($wi['machines'])) {
            $equipments = [];
            foreach ($wi['machines'] as $machine) {
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
}
