<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Enum\Day;
use App\Entity\Enum\Equipment;
use App\Entity\Enum\GeolocationStatus;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Enum\ProfessionalStatus;
use App\Entity\Laundromat;
use App\Entity\LaundromatClosure;
use App\Entity\LaundromatEquipment;
use App\Entity\LaundromatMedia;
use App\Entity\Media;
use App\Entity\Professional;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\LaundromatRepository;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function count;
use function array_values;
use function bin2hex;
use function dirname;
use function file_exists;
use function in_array;
use function is_array;
use function is_dir;
use function is_numeric;
use function is_string;
use function mkdir;
use function pathinfo;
use function random_bytes;
use function trim;

#[Route('/api/pro/laundries', name: 'api_pro_laundries_')]
class ProfessionalLaundryController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly LaundromatRepository $laundromatRepository,
        private readonly ServiceRepository $serviceRepository,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $professional = $this->getValidatedProfessional();
            if (!$professional instanceof Professional) {
                return $professional;
            }

            $laundromats = $this->laundromatRepository->findBy([
                'professional' => $professional,
                'deletedAt' => null,
            ], [
                'addedDate' => 'DESC',
            ]);

            return $this->json(array_map($this->serializeLaundry(...), $laundromats), Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $professional = $this->getValidatedProfessional();
            if (!$professional instanceof Professional) {
                return $professional;
            }

            $data = $this->extractPayload($request);

            if (!is_array($data)) {
                return $this->json([
                    'error' => 'api.messages.invalid_payload',
                ], Response::HTTP_BAD_REQUEST);
            }

            $laundromat = new Laundromat();
            $laundromat->setProfessional($professional);
            $laundromat->setLogo($this->createPlaceholderLogo());

            $errorResponse = $this->hydrateLaundromat($laundromat, $data, $this->extractPhotos($request));
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
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $professional = $this->getValidatedProfessional();
            if (!$professional instanceof Professional) {
                return $professional;
            }

            $laundry = $this->laundromatRepository->find($id);
            if (!$laundry instanceof Laundromat || $laundry->getDeletedAt() !== null) {
                return $this->json([
                    'error' => 'api.messages.laundry_not_found',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($laundry->getProfessional()?->getId() !== $professional->getId()) {
                return $this->json([
                    'error' => 'api.messages.laundry_forbidden',
                ], Response::HTTP_FORBIDDEN);
            }

            $data = $this->extractPayload($request);

            if (!is_array($data)) {
                return $this->json([
                    'error' => 'api.messages.invalid_payload',
                ], Response::HTTP_BAD_REQUEST);
            }

            $errorResponse = $this->hydrateLaundromat($laundry, $data, $this->extractPhotos($request));
            if ($errorResponse instanceof JsonResponse) {
                return $errorResponse;
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'api.messages.laundry_updated',
                'laundry' => $this->serializeLaundry($laundry),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getValidatedProfessional(): Professional|JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json([
                'error' => 'api.messages.profile_forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        $professional = $currentUser->getProfessional();
        if (!$professional instanceof Professional || $professional->getStatus() !== ProfessionalStatus::Validated) {
            return $this->json([
                'error' => 'api.messages.profile_forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        return $professional;
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

    private function hydrateLaundromat(Laundromat $laundromat, array $data, array $photos = []): ?JsonResponse
    {
        if (
            !isset($data['establishmentName'])
            || !isset($data['description'])
            || !isset($data['street'])
            || !isset($data['zipCode'])
            || !isset($data['city'])
        ) {
            return $this->json([
                'error' => 'api.messages.missing_fields',
            ], Response::HTTP_BAD_REQUEST);
        }

        $address = $laundromat->getAddress() ?? new Address();
        $fullAddress = trim($data['street']) . ', ' . trim((string) $data['zipCode']) . ' ' . trim($data['city']) . ', ' . trim((string) ($data['country'] ?? 'France'));

        $address->setAddress($fullAddress);
        $address->setStreet(trim($data['street']));
        $address->setZipCode((int) $data['zipCode']);
        $address->setCity(trim($data['city']));
        $address->setCountry(trim((string) ($data['country'] ?? 'France')));
        $address->setGeolocationStatus(GeolocationStatus::Pending);

        $now = new \DateTimeImmutable();

        $laundromat->setAddress($address);
        $laundromat->setEstablishmentName(trim($data['establishmentName']));
        $laundromat->setDescription(trim($data['description']));
        $laundromat->setContactEmail(isset($data['contactEmail']) && is_string($data['contactEmail']) ? trim($data['contactEmail']) : null);
        $laundromat->setUpdatedAt($now);

        if ($laundromat->getAddedDate() === null) {
            $laundromat->setAddedDate($now);
            $laundromat->setStatus(LaundromatStatus::Pending);
        } else {
            $laundromat->setStatus(LaundromatStatus::Pending);
        }

        $wiLineClientCode = $data['wiLineClientCode'] ?? null;
        $laundromat->setWiLineReference(is_numeric($wiLineClientCode) ? (int) $wiLineClientCode : null);

        $this->syncServices($laundromat, $data['services'] ?? []);
        $closuresError = $this->syncClosures($laundromat, $data['openingHours'] ?? [], (bool) ($data['isOpenTwentyFourSeven'] ?? false), $now);
        if ($closuresError instanceof JsonResponse) {
            return $closuresError;
        }

        $equipmentsError = $this->syncEquipments($laundromat, $data['machines'] ?? []);
        if ($equipmentsError instanceof JsonResponse) {
            return $equipmentsError;
        }

        $photosError = $this->syncPhotos($laundromat, $photos);
        if ($photosError instanceof JsonResponse) {
            return $photosError;
        }

        $addressErrors = $this->validator->validate($address);
        if (count($addressErrors) > 0) {
            return $this->json([
                'error' => (string) $addressErrors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $laundromatErrors = $this->validator->validate($laundromat);
        if (count($laundromatErrors) > 0) {
            return $this->json([
                'error' => (string) $laundromatErrors,
            ], Response::HTTP_BAD_REQUEST);
        }

        foreach ($laundromat->getClosures() as $closure) {
            $closureErrors = $this->validator->validate($closure);
            if (count($closureErrors) > 0) {
                return $this->json([
                    'error' => (string) $closureErrors,
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($laundromat->getEquipments() as $equipment) {
            $equipmentErrors = $this->validator->validate($equipment);
            if (count($equipmentErrors) > 0) {
                return $this->json([
                    'error' => (string) $equipmentErrors,
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($laundromat->getMedias() as $mediaRelation) {
            $mediaRelationErrors = $this->validator->validate($mediaRelation);
            if (count($mediaRelationErrors) > 0) {
                return $this->json([
                    'error' => (string) $mediaRelationErrors,
                ], Response::HTTP_BAD_REQUEST);
            }

            $mediaErrors = $this->validator->validate($mediaRelation->getMedia());
            if (count($mediaErrors) > 0) {
                return $this->json([
                    'error' => (string) $mediaErrors,
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        return null;
    }

    private function syncServices(Laundromat $laundromat, mixed $services): void
    {
        $laundromatServices = $laundromat->getServices();
        $laundromatServices?->clear();

        if (!is_array($services)) {
            return;
        }

        foreach ($services as $serviceName) {
            if (!is_string($serviceName) || trim($serviceName) === '') {
                continue;
            }

            $normalizedServiceName = trim($serviceName);
            $service = $this->serviceRepository->findOneBy(['name' => $normalizedServiceName]);

            if (!$service instanceof Service) {
                $service = new Service();
                $service->setName($normalizedServiceName);
                $this->entityManager->persist($service);
            }

            $laundromatServices?->add($service);
        }
    }

    private function syncClosures(Laundromat $laundromat, mixed $openingHours, bool $isOpenTwentyFourSeven, \DateTimeImmutable $now): ?JsonResponse
    {
        $closures = $laundromat->getClosures();
        if ($closures instanceof Collection) {
            foreach ($closures as $closure) {
                $this->entityManager->remove($closure);
            }
            $closures->clear();
        }

        if ($isOpenTwentyFourSeven) {
            $openingHours = [
                ['day' => 'monday', 'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'tuesday', 'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'wednesday', 'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'thursday', 'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'friday', 'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'saturday', 'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'sunday', 'startTime' => '00:00', 'endTime' => '23:59'],
            ];
        }

        if (!is_array($openingHours) || $openingHours === []) {
            return $this->json([
                'error' => 'api.messages.missing_fields',
            ], Response::HTTP_BAD_REQUEST);
        }

        foreach ($openingHours as $openingHour) {
            if (
                !is_array($openingHour)
                || !isset($openingHour['day'])
                || !isset($openingHour['startTime'])
                || !isset($openingHour['endTime'])
            ) {
                return $this->json([
                    'error' => 'api.messages.missing_fields',
                ], Response::HTTP_BAD_REQUEST);
            }

            $day = Day::tryFrom((string) $openingHour['day']);
            if (!$day instanceof Day) {
                return $this->json([
                    'error' => 'api.messages.invalid_schedule',
                ], Response::HTTP_BAD_REQUEST);
            }

            $startTime = \DateTimeImmutable::createFromFormat('H:i', (string) $openingHour['startTime']);
            $endTime = \DateTimeImmutable::createFromFormat('H:i', (string) $openingHour['endTime']);

            if (!$startTime instanceof \DateTimeImmutable || !$endTime instanceof \DateTimeImmutable || $startTime >= $endTime) {
                return $this->json([
                    'error' => 'api.messages.invalid_schedule',
                ], Response::HTTP_BAD_REQUEST);
            }

            $closure = new LaundromatClosure();
            $closure->setLaundromat($laundromat);
            $closure->setDay($day);
            $closure->setAddedDate($now);
            $closure->setUpdatedAt($now);
            $closure->setStartTime($startTime);
            $closure->setEndTime($endTime);

            $closures?->add($closure);
            $this->entityManager->persist($closure);
        }

        return null;
    }

    private function syncEquipments(Laundromat $laundromat, mixed $machines): ?JsonResponse
    {
        $equipments = $laundromat->getEquipments();
        if ($equipments instanceof Collection) {
            foreach ($equipments as $equipment) {
                $this->entityManager->remove($equipment);
            }
            $equipments->clear();
        }

        if (!is_array($machines)) {
            return null;
        }

        foreach ($machines as $machine) {
            if (
                !is_array($machine)
                || !isset($machine['type'])
                || !isset($machine['capacity'])
                || !isset($machine['price'])
                || !isset($machine['duration'])
            ) {
                return $this->json([
                    'error' => 'api.messages.missing_fields',
                ], Response::HTTP_BAD_REQUEST);
            }

            $type = Equipment::tryFrom((string) $machine['type']);
            if (!$type instanceof Equipment) {
                return $this->json([
                    'error' => 'api.messages.invalid_equipment_type',
                ], Response::HTTP_BAD_REQUEST);
            }

            $capacity = (int) $machine['capacity'];
            $duration = (int) $machine['duration'];
            $price = number_format((float) $machine['price'], 2, '.', '');

            if ($capacity <= 0 || $duration <= 0 || (float) $price < 0) {
                return $this->json([
                    'error' => 'api.messages.invalid_equipment',
                ], Response::HTTP_BAD_REQUEST);
            }

            $equipment = new LaundromatEquipment();
            $equipment->setLaundromat($laundromat);
            $equipment->setType($type);
            $equipment->setCapacity($capacity);
            $equipment->setPrice($price);
            $equipment->setDuration($duration);
            $equipment->setName($this->buildEquipmentName($type, $capacity));

            if (isset($machine['equipmentReference']) && is_numeric($machine['equipmentReference'])) {
                $equipment->setEquipmentReference((int) $machine['equipmentReference']);
            }

            $equipments?->add($equipment);
            $this->entityManager->persist($equipment);
        }

        return null;
    }

    /**
     * @param UploadedFile[] $photos
     */
    private function syncPhotos(Laundromat $laundromat, array $photos): ?JsonResponse
    {
        if ($photos === []) {
            return null;
        }

        foreach ($laundromat->getMedias() as $mediaRelation) {
            $this->entityManager->remove($mediaRelation);
        }
        $laundromat->getMedias()->clear();

        $uploadDirectory = dirname(__DIR__, 2) . '/public/uploads/laundry-photos';
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        foreach ($photos as $index => $photo) {
            $mimeType = $photo->getMimeType() ?? '';
            if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                return $this->json([
                    'error' => 'api.messages.invalid_photo',
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($photo->getSize() !== null && $photo->getSize() > 5 * 1024 * 1024) {
                return $this->json([
                    'error' => 'api.messages.invalid_photo',
                ], Response::HTTP_BAD_REQUEST);
            }

            $fileSize = $photo->getSize() ?? 0;
            $extension = $photo->guessExtension() ?: pathinfo($photo->getClientOriginalName(), PATHINFO_EXTENSION);
            $filename = 'laundry-' . bin2hex(random_bytes(8)) . ($extension ? '.' . $extension : '');
            $photo->move($uploadDirectory, $filename);

            $media = new Media();
            $media->setLocation('/uploads/laundry-photos/' . $filename);
            $media->setOriginalName($photo->getClientOriginalName());
            $media->setSize($fileSize);
            $media->setMimeType($mimeType);

            $mediaRelation = new LaundromatMedia();
            $mediaRelation->setLaundromat($laundromat);
            $mediaRelation->setMedia($media);
            $mediaRelation->setDescription('Laundry photo ' . ($index + 1));

            if ($index === 0 || !($laundromat->getLogo() instanceof Media) || !file_exists(dirname(__DIR__, 2) . '/public' . $laundromat->getLogo()->getLocation())) {
                $laundromat->setLogo($media);
            }

            $laundromat->getMedias()->add($mediaRelation);
            $this->entityManager->persist($mediaRelation);
        }

        return null;
    }

    private function buildEquipmentName(Equipment $type, int $capacity): string
    {
        if ($type === Equipment::Dryer) {
            return 'Sèche-linge ' . $capacity . ' kg';
        }

        return 'Lave-linge ' . $capacity . ' kg';
    }

    private function createPlaceholderLogo(): Media
    {
        $logo = new Media();
        $logo->setLocation('/uploads/placeholders/laundry-logo.png');
        $logo->setOriginalName('placeholder-logo.png');
        $logo->setSize(0);
        $logo->setMimeType('image/png');

        return $logo;
    }

    private function serializeLaundry(Laundromat $laundromat): array
    {
        $address = $laundromat->getAddress();

        $machines = [];
        foreach ($laundromat->getEquipments() ?? [] as $equipment) {
            $machines[] = [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
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
            'wiLineReference' => $laundromat->getWiLineReference(),
            'status' => $laundromat->getStatus()?->value,
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
