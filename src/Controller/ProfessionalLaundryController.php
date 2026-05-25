<?php

namespace App\Controller;

use App\Entity\Enum\LaundromatStatus;
use App\Entity\Enum\ProfessionalStatus;
use App\Entity\Laundromat;
use App\Entity\LaundromatExceptionalClosure;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\LaundromatRepository;
use App\Service\LaundromatHydrator;
use App\Service\WiLineApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pro/laundries', name: 'api_pro_laundries_')]
class ProfessionalLaundryController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LaundromatRepository $laundromatRepository,
        private readonly LaundromatHydrator $laundromatHydrator,
        private readonly WiLineApiService $wiLineApiService,
    ) {}

    #[Route('/wiline/{serial}', name: 'wiline_details', methods: ['GET'])]
    public function getWiLineDetails(string $serial): JsonResponse
    {
        try {
            $this->getProfessional();

            $normalizedSerial = trim($serial);
            $wiLineDetails = $this->wiLineApiService->getLaundryDetails($normalizedSerial);
            $machines = $this->wiLineApiService->getLaundryMachines($normalizedSerial);
            $normalizedMachines = [];
            $warnings = [];

            foreach ($machines as $machine) {
                if (!is_array($machine)) {
                    continue;
                }

                if (($machine['out_of_order'] ?? false) === true) {
                    continue;
                }

                $rawTypeName = trim((string) ($machine['type_name'] ?? ''));
                $normalizedTypeName = mb_strtolower($rawTypeName);
                $type = null;

                if (str_starts_with($normalizedTypeName, 'machine')) {
                    $type = 'washer';
                } elseif (str_starts_with($normalizedTypeName, 'séchoir') || str_starts_with($normalizedTypeName, 'sechoir')) {
                    $type = 'dryer';
                }

                if ($type === null) {
                    $warnings[] = 'api.messages.wiline_unsupported_machine_category';
                    continue;
                }

                preg_match('/(\d+)\s*kg/i', $rawTypeName, $capacityMatch);
                $capacity = isset($capacityMatch[1]) ? (int) $capacityMatch[1] : 8;

                $rawPrice = (float) ($machine['price'] ?? 0);
                $priceInEuros = $rawPrice > 0 ? round($rawPrice / 100, 2) : 0.0;

                $rawDuration = (int) ($machine['duration'] ?? 0);
                $duration = $rawDuration > 120 ? (int) round($rawDuration / 60) : $rawDuration;
                if ($duration <= 0) {
                    $duration = 1;
                }

                $normalizedMachines[] = [
                    'type' => $type,
                    'capacity' => $capacity,
                    'price' => $priceInEuros,
                    'duration' => $duration,
                    'equipmentReference' => isset($machine['machine_number']) ? (int) $machine['machine_number'] : null,
                ];
            }

            return $this->json([
                'serial' => $normalizedSerial,
                'details' => [
                    'establishmentName' => (string) ($wiLineDetails['name'] ?? ''),
                    'street' => (string) ($wiLineDetails['address'] ?? ''),
                    'zipCode' => (string) ($wiLineDetails['postal_code'] ?? ''),
                    'city' => (string) ($wiLineDetails['city'] ?? ''),
                    'country' => (string) ($wiLineDetails['country'] ?? 'France'),
                ],
                'machines' => $normalizedMachines,
                'warnings' => $warnings,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $professional = $this->getProfessional();

            $laundromats = $this->laundromatRepository->findBy(
                ['professional' => $professional, 'deletedAt' => null],
                ['addedDate' => 'DESC'],
            );

            return $this->json(array_map($this->serializeLaundry(...), $laundromats), Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $professional = $this->getProfessional();

            $data = $this->extractPayload($request);
            if (!is_array($data)) {
                return $this->json(['error' => 'api.messages.invalid_payload'], Response::HTTP_BAD_REQUEST);
            }

            $laundromat = new Laundromat();
            $laundromat->setProfessional($professional);
            $laundromat->setLogo($this->laundromatHydrator->createPlaceholderLogo());
            $laundromat->setStatus(LaundromatStatus::Pending);

            $errorResponse = $this->laundromatHydrator->hydrate($laundromat, $data, $this->extractPhotos($request));
            if ($errorResponse instanceof JsonResponse) {
                return $errorResponse;
            }

            $this->entityManager->persist($laundromat);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'api.messages.laundry_created',
                'laundry' => $this->serializeLaundry($laundromat),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($id);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            return $this->json($this->serializeLaundry($laundry), Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($id);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            $data = $this->extractPayload($request);
            if (!is_array($data)) {
                return $this->json(['error' => 'api.messages.invalid_payload'], Response::HTTP_BAD_REQUEST);
            }

            $status = $laundry->getStatus();

            if ($status === LaundromatStatus::Validated) {
                $laundry->setPendingChanges($data);
                $laundry->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                return $this->json([
                    'message' => 'api.messages.laundry_pending_review',
                    'laundry' => $this->serializeLaundry($laundry),
                ], Response::HTTP_OK);
            }

            $errorResponse = $this->laundromatHydrator->hydrate($laundry, $data, $this->extractPhotos($request));
            if ($errorResponse instanceof JsonResponse) {
                return $errorResponse;
            }

            $laundry->setStatus(LaundromatStatus::Pending);
            $laundry->setPendingChanges(null);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'api.messages.laundry_updated',
                'laundry' => $this->serializeLaundry($laundry),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($id);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            $laundry->setDeletedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return $this->json(['message' => 'api.messages.laundry_deleted'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/exceptional-closures', name: 'exceptional_closures_list', methods: ['GET'])]
    public function listExceptionalClosures(int $id): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($id);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            $data = [];
            foreach ($laundry->getExceptionalClosures() as $closure) {
                $data[] = [
                    'id' => $closure->getId(),
                    'startDate' => $closure->getStartDate()?->format('Y-m-d'),
                    'endDate' => $closure->getEndDate()?->format('Y-m-d'),
                    'reason' => $closure->getReason(),
                ];
            }

            return $this->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/exceptional-closures', name: 'exceptional_closures_create', methods: ['POST'])]
    public function createExceptionalClosure(int $id, Request $request): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($id);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            $data = json_decode($request->getContent(), true);
            $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($data['startDate'] ?? ''));
            $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($data['endDate'] ?? ''));

            if (!$startDate instanceof \DateTimeImmutable || !$endDate instanceof \DateTimeImmutable) {
                return $this->json(['error' => 'api.messages.invalid_date_format'], Response::HTTP_BAD_REQUEST);
            }

            if ($startDate > $endDate) {
                return $this->json(['error' => 'api.messages.invalid_date_range'], Response::HTTP_BAD_REQUEST);
            }

            $closure = new LaundromatExceptionalClosure();
            $closure->setLaundromat($laundry);
            $closure->setStartDate($startDate);
            $closure->setEndDate($endDate);
            $closure->setAddedDate(new \DateTimeImmutable());
            if (!empty($data['reason'])) {
                $closure->setReason((string) $data['reason']);
            }

            $this->entityManager->persist($closure);
            $this->entityManager->flush();

            return $this->json([
                'id' => $closure->getId(),
                'startDate' => $closure->getStartDate()->format('Y-m-d'),
                'endDate' => $closure->getEndDate()->format('Y-m-d'),
                'reason' => $closure->getReason(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/exceptional-closures/{closureId}', name: 'exceptional_closures_delete', methods: ['DELETE'])]
    public function deleteExceptionalClosure(int $id, int $closureId): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($id);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            $closure = $this->entityManager->find(LaundromatExceptionalClosure::class, $closureId);
            if (!$closure instanceof LaundromatExceptionalClosure || $closure->getLaundromat()?->getId() !== $id) {
                return $this->json(['error' => 'api.messages.not_found'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($closure);
            $this->entityManager->flush();

            return $this->json(['message' => 'api.messages.deleted'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getOwnedLaundry(int $id): Laundromat|JsonResponse
    {
        $professional = $this->getProfessional();

        $laundry = $this->laundromatRepository->find($id);
        if (!$laundry instanceof Laundromat || $laundry->getDeletedAt() !== null) {
            return $this->json(['error' => 'api.messages.laundry_not_found'], Response::HTTP_NOT_FOUND);
        }

        if ($laundry->getProfessional()?->getId() !== $professional->getId()) {
            return $this->json(['error' => 'api.messages.laundry_forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $laundry;
    }

    private function extractPayload(Request $request): ?array
    {
        $formPayload = $request->request->get('data');
        if (is_string($formPayload) && $formPayload !== '') {
            $decodedPayload = json_decode($formPayload, true);

            return is_array($decodedPayload) ? $decodedPayload : null;
        }

        $rawPayload = json_decode($request->getContent(), true);

        return is_array($rawPayload) ? $rawPayload : null;
    }

    /**
     * @return UploadedFile[]
     */
    private function extractPhotos(Request $request): array
    {
        $photos = $request->files->all('photos');

        return array_values(array_filter($photos, static fn (mixed $photo): bool => $photo instanceof UploadedFile));
    }

    private function serializeLaundry(Laundromat $laundromat): array
    {
        $address = $laundromat->getAddress();

        $machines = [];
        foreach ($laundromat->getEquipments() ?? [] as $equipment) {
            $machines[] = [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
                'nameTranslationParams' => [
                    'capacity' => $equipment->getCapacity(),
                ],
                'type' => $equipment->getType()?->value,
                'capacity' => $equipment->getCapacity(),
                'price' => $equipment->getPrice(),
                'duration' => $equipment->getDuration(),
                'equipmentReference' => $equipment->getEquipmentReference(),
            ];
        }

        $openingHours = [];
        foreach ($laundromat->getClosures() ?? [] as $closure) {
            $openingHours[] = [
                'id' => $closure->getId(),
                'day' => $closure->getDay()?->value,
                'startTime' => $closure->getStartTime()?->format('H:i'),
                'endTime' => $closure->getEndTime()?->format('H:i'),
            ];
        }

        $services = [];
        foreach ($laundromat->getServices() ?? [] as $service) {
            $services[] = $service->getName();
        }

        $paymentMethods = [];
        foreach ($laundromat->getPaymentMethods() ?? [] as $paymentMethod) {
            $paymentMethods[] = $paymentMethod->getName();
        }

        $photos = [];
        foreach ($laundromat->getMedias() ?? [] as $mediaRelation) {
            $media = $mediaRelation->getMedia();
            $photos[] = [
                'id' => $mediaRelation->getId(),
                'url' => $media?->getLocation(),
                'name' => $media?->getOriginalName(),
                'description' => $mediaRelation->getDescription(),
            ];
        }

        return [
            'id' => $laundromat->getId(),
            'establishmentName' => $laundromat->getEstablishmentName(),
            'description' => $laundromat->getDescription(),
            'contactEmail' => $laundromat->getContactEmail(),
            'contactPhone' => $laundromat->getContactPhone(),
            'wiLineReference' => $laundromat->getWiLineReference(),
            'status' => $laundromat->getStatus()?->value,
            'hasPendingChanges' => $laundromat->hasPendingChanges(),
            'addedDate' => $laundromat->getAddedDate()?->format('Y-m-d'),
            'updatedAt' => $laundromat->getUpdatedAt()?->format('Y-m-d'),
            'address' => [
                'street' => $address?->getStreet(),
                'zipCode' => $address?->getZipCode(),
                'city' => $address?->getCity(),
                'country' => $address?->getCountry(),
                'fullAddress' => $address?->getAddress(),
            ],
            'services' => $services,
            'paymentMethods' => $paymentMethods,
            'machines' => $machines,
            'openingHours' => $openingHours,
            'isOpenTwentyFourSeven' => $this->isOpenTwentyFourSeven($openingHours),
            'photos' => $photos,
        ];
    }

    private function isOpenTwentyFourSeven(array $openingHours): bool
    {
        if (count($openingHours) !== 7) {
            return false;
        }

        foreach ($openingHours as $openingHour) {
            if (
                !is_array($openingHour)
                || !isset($openingHour['startTime'], $openingHour['endTime'])
                || $openingHour['startTime'] !== '00:00'
                || $openingHour['endTime'] !== '23:59'
            ) {
                return false;
            }
        }

        return true;
    }
}
