<?php

namespace App\Controller;

use App\Entity\Enum\LaundromatStatus;
use App\Entity\Enum\Day;
use App\Entity\Enum\LaundromatExceptionalClosureType;
use App\Entity\Laundromat;
use App\Entity\LaundromatExceptionalClosure;
use App\Entity\LaundromatExceptionalClosureSlot;
use App\Entity\LaundromatRating;
use App\Entity\User;
use App\Repository\LaundromatRepository;
use App\Repository\LaundromatRatingRepository;
use App\Service\LaundromatHydrator;
use App\Service\LaundromatSerializer;
use App\Service\RatingSerializer;
use App\Service\WiLineApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
        private readonly LaundromatRatingRepository $ratingRepository,
        private readonly LaundromatHydrator $laundromatHydrator,
        private readonly LaundromatSerializer $laundromatSerializer,
        private readonly RatingSerializer $ratingSerializer,
        private readonly WiLineApiService $wiLineApiService,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
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

                $normalizedMachine = $this->normalizeWiLineMachine($machine, $warnings);
                if ($normalizedMachine !== null) {
                    $normalizedMachines[] = $normalizedMachine;
                }
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
            $laundromats = $this->laundromatRepository->findByProfessionalWithDetails($professional);

            $serialized = [];
            foreach ($laundromats as $laundromat) {
                $serialized[] = $this->laundromatSerializer->serializeForProfessional($laundromat);
            }

            return $this->json($serialized, Response::HTTP_OK);
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

            $laundromatWithDetails = $this->laundromatRepository->findWithDetails((int) $laundromat->getId());

            return $this->json([
                'message' => 'api.messages.laundry_created',
                'laundry' => $this->laundromatSerializer->serializeForProfessional($laundromatWithDetails ?? $laundromat),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $ownedLaundryResponse = $this->getOwnedLaundry($id);
            if ($ownedLaundryResponse instanceof JsonResponse) {
                return $ownedLaundryResponse;
            }

            $laundromat = $this->laundromatRepository->findWithDetails($id);

            return $this->json(
                $this->laundromatSerializer->serializeForProfessional($laundromat ?? $ownedLaundryResponse),
                Response::HTTP_OK,
            );
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

                $laundromatWithDetails = $this->laundromatRepository->findWithDetails($id);

                return $this->json([
                    'message' => 'api.messages.laundry_pending_review',
                    'laundry' => $this->laundromatSerializer->serializeForProfessional($laundromatWithDetails ?? $laundry),
                ], Response::HTTP_OK);
            }

            $errorResponse = $this->laundromatHydrator->hydrate($laundry, $data, $this->extractPhotos($request));
            if ($errorResponse instanceof JsonResponse) {
                return $errorResponse;
            }

            $laundry->setStatus(LaundromatStatus::Pending);
            $laundry->setPendingChanges(null);
            $this->entityManager->flush();

            $laundromatWithDetails = $this->laundromatRepository->findWithDetails($id);

            return $this->json([
                'message' => 'api.messages.laundry_updated',
                'laundry' => $this->laundromatSerializer->serializeForProfessional($laundromatWithDetails ?? $laundry),
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

            $laundromat = $this->laundromatRepository->findWithDetails($id);
            $data = [];
            foreach (($laundromat ?? $laundry)->getExceptionalClosures() as $closure) {
                $data[] = $this->laundromatSerializer->serializeExceptionalClosureForProfessional($closure);
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

            $data = json_decode($request->getContent(), true) ?? [];
            $startDate = $this->parseDateTime((string) ($data['startDate'] ?? ''));
            $endDate = $this->parseDateTime((string) ($data['endDate'] ?? ''));
            $type = $this->parseExceptionalClosureType($data['type'] ?? LaundromatExceptionalClosureType::FullClosure->value);

            if (!$startDate instanceof \DateTimeImmutable || !$endDate instanceof \DateTimeImmutable || !$type instanceof LaundromatExceptionalClosureType) {
                return $this->json(['error' => 'api.messages.invalid_date_format'], Response::HTTP_BAD_REQUEST);
            }

            if ($startDate >= $endDate) {
                return $this->json(['error' => 'api.messages.invalid_date_range'], Response::HTTP_BAD_REQUEST);
            }

            $closure = new LaundromatExceptionalClosure();
            $closure->setLaundromat($laundry);
            $closure->setStartDate($startDate);
            $closure->setEndDate($endDate);
            $closure->setType($type);
            $closure->setAddedDate(new \DateTimeImmutable());
            if (!empty($data['reason'])) {
                $closure->setReason((string) $data['reason']);
            }

            if ($type === LaundromatExceptionalClosureType::ModifiedHours) {
                $openingHours = $data['openingHours'] ?? null;
                if (!is_array($openingHours) || $openingHours === []) {
                    return $this->json(['error' => 'api.messages.invalid_laundromat_closure'], Response::HTTP_BAD_REQUEST);
                }

                $slotError = $this->syncExceptionalClosureSlots($closure, $openingHours, new \DateTimeImmutable());
                if ($slotError instanceof JsonResponse) {
                    return $slotError;
                }
            }

            $this->entityManager->persist($closure);
            $this->entityManager->flush();

            $this->notifyFavoriteUsersAboutExceptionalClosure($laundry, $closure);

            return $this->json(
                $this->laundromatSerializer->serializeExceptionalClosureForProfessional($closure),
                Response::HTTP_CREATED,
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{laundromatId}/ratings/{ratingId}/response', name: 'ratings_response_create', methods: ['POST'])]
    public function createRatingResponse(int $laundromatId, int $ratingId, Request $request): JsonResponse
    {
        return $this->upsertRatingResponse($laundromatId, $ratingId, $request, false);
    }

    #[Route('/{laundromatId}/ratings/{ratingId}/response', name: 'ratings_response_update', methods: ['PATCH'])]
    public function updateRatingResponse(int $laundromatId, int $ratingId, Request $request): JsonResponse
    {
        return $this->upsertRatingResponse($laundromatId, $ratingId, $request, true);
    }

    #[Route('/{laundromatId}/ratings/{ratingId}/response', name: 'ratings_response_delete', methods: ['DELETE'])]
    public function deleteRatingResponse(int $laundromatId, int $ratingId): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($laundromatId);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            $rating = $this->ratingRepository->find($ratingId);
            if (!$rating instanceof LaundromatRating || $rating->getLaundromat()?->getId() !== $laundromatId) {
                return $this->json(['error' => 'api.messages.rating_not_found'], Response::HTTP_NOT_FOUND);
            }

            $rating->setResponse(null);
            $rating->setRespondedAt(null);
            $this->entityManager->flush();

            return $this->json(null, Response::HTTP_NO_CONTENT);
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

    /**
     * @param array<string, mixed> $machine
     * @param list<string>         $warnings
     *
     * @return array<string, mixed>|null
     */
    private function normalizeWiLineMachine(array $machine, array &$warnings): ?array
    {
        if (($machine['out_of_order'] ?? false) === true) {
            return null;
        }

        $rawTypeName = trim((string) ($machine['type_name'] ?? ''));
        $normalizedTypeName = mb_strtolower($rawTypeName);

        $type = match (true) {
            str_starts_with($normalizedTypeName, 'machine') => 'washer',
            str_starts_with($normalizedTypeName, 'séchoir') || str_starts_with($normalizedTypeName, 'sechoir') => 'dryer',
            default => null,
        };

        if ($type === null) {
            $warnings[] = 'api.messages.wiline_unsupported_machine_category';

            return null;
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

        return [
            'type' => $type,
            'capacity' => $capacity,
            'price' => $priceInEuros,
            'duration' => $duration,
            'equipmentReference' => isset($machine['machine_number']) ? (int) $machine['machine_number'] : null,
        ];
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

    private function upsertRatingResponse(int $laundromatId, int $ratingId, Request $request, bool $isUpdate): JsonResponse
    {
        try {
            $laundry = $this->getOwnedLaundry($laundromatId);
            if ($laundry instanceof JsonResponse) {
                return $laundry;
            }

            $rating = $this->ratingRepository->find($ratingId);
            if (!$rating instanceof LaundromatRating || $rating->getLaundromat()?->getId() !== $laundromatId) {
                return $this->json(['error' => 'api.messages.rating_not_found'], Response::HTTP_NOT_FOUND);
            }

            $currentResponse = $rating->getResponse();
            if (!$isUpdate && $currentResponse !== null) {
                return $this->json(['error' => 'api.messages.rating_response_already_exists'], Response::HTTP_CONFLICT);
            }

            if ($isUpdate && $currentResponse === null) {
                return $this->json(['error' => 'api.messages.rating_response_not_found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $response = trim((string) ($data['response'] ?? ''));
            if ($response === '' || mb_strlen($response) > 500) {
                return $this->json(['error' => 'api.messages.comment_too_long'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $rating->setResponse($response);
            $rating->setRespondedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            $this->notifyRatingAuthorAboutResponse($rating, $isUpdate);

            return $this->json(
                $this->ratingSerializer->serializePublic($rating),
                $isUpdate ? Response::HTTP_OK : Response::HTTP_CREATED,
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array<int, mixed> $openingHours
     */
    private function syncExceptionalClosureSlots(LaundromatExceptionalClosure $closure, array $openingHours, \DateTimeImmutable $now): ?JsonResponse
    {
        foreach ($openingHours as $openingHour) {
            if (!is_array($openingHour)) {
                return $this->json(['error' => 'api.messages.invalid_laundromat_closure'], Response::HTTP_BAD_REQUEST);
            }

            $day = isset($openingHour['day']) ? Day::tryFrom((string) $openingHour['day']) : null;
            $startTime = $this->parseTime((string) ($openingHour['startTime'] ?? ''));
            $endTime = $this->parseTime((string) ($openingHour['endTime'] ?? ''));

            if (!$day instanceof Day || !$startTime instanceof \DateTimeImmutable || !$endTime instanceof \DateTimeImmutable || $startTime >= $endTime) {
                return $this->json(['error' => 'api.messages.invalid_laundromat_closure'], Response::HTTP_BAD_REQUEST);
            }

            $slot = new LaundromatExceptionalClosureSlot();
            $slot->setExceptionalClosure($closure);
            $slot->setDay($day);
            $slot->setStartTime($startTime);
            $slot->setEndTime($endTime);
            $slot->setAddedDate($now);
            $slot->setUpdatedAt($now);
            $this->entityManager->persist($slot);
        }

        return null;
    }

    private function parseExceptionalClosureType(mixed $type): ?LaundromatExceptionalClosureType
    {
        if (!is_string($type) || $type === '') {
            return null;
        }

        return LaundromatExceptionalClosureType::tryFrom($type);
    }

    private function parseDateTime(string $value): ?\DateTimeImmutable
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        foreach (['Y-m-d\TH:i', \DateTimeInterface::ATOM, 'Y-m-d H:i', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $normalized);
            if ($date instanceof \DateTimeImmutable) {
                return $date;
            }
        }

        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Exception) {
            return null;
        }
    }

    private function parseTime(string $value): ?\DateTimeImmutable
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('H:i', $normalized) ?: null;
    }

    private function notifyRatingAuthorAboutResponse(LaundromatRating $rating, bool $isUpdate): void
    {
        $author = $rating->getUser();
        $laundromat = $rating->getLaundromat();
        if (!$author instanceof User || $author->getEmail() === null || !$laundromat instanceof Laundromat) {
            return;
        }

        try {
            $email = (new Email())
                ->to($author->getEmail())
                ->subject($isUpdate ? 'Votre avis a recu une reponse mise a jour' : 'Votre avis a recu une reponse')
                ->text(sprintf(
                    "Bonjour,\n\nLe professionnel de la laverie %s a %s a votre avis.\n\nCordialement,\nLaundry Map",
                    $laundromat->getEstablishmentName(),
                    $isUpdate ? 'mis a jour sa reponse' : 'repondu'
                ));

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send rating response email: '.$e->getMessage());
        }
    }

    private function notifyFavoriteUsersAboutExceptionalClosure(Laundromat $laundromat, LaundromatExceptionalClosure $closure): void
    {
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.favoriteLaundromats', 'l')
            ->andWhere('l = :laundromat')
            ->setParameter('laundromat', $laundromat)
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            if (!$user instanceof User || $user->getEmail() === null) {
                continue;
            }

            try {
                $email = (new Email())
                    ->to($user->getEmail())
                    ->subject('Mise a jour exceptionnelle de votre laverie favorite')
                    ->text(sprintf(
                        "Bonjour,\n\nLa laverie %s a declare une exception du %s au %s.%s\n\nCordialement,\nLaundry Map",
                        $laundromat->getEstablishmentName(),
                        $closure->getStartDate()?->format('d/m/Y H:i'),
                        $closure->getEndDate()?->format('d/m/Y H:i'),
                        $closure->getReason() ? "\nMotif : ".$closure->getReason() : ''
                    ));

                $this->mailer->send($email);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send exceptional closure email: '.$e->getMessage());
            }
        }
    }
}
