<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Enum\Day;
use App\Entity\Enum\Equipment;
use App\Entity\Enum\GeolocationStatus;
use App\Entity\Laundromat;
use App\Entity\LaundromatClosure;
use App\Entity\LaundromatEquipment;
use App\Entity\LaundromatMedia;
use App\Entity\Media;
use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LaundromatHydrator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly ServiceRepository $serviceRepository,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @param UploadedFile[] $photos
     */
    public function hydrate(Laundromat $laundromat, array $data, array $photos = []): ?JsonResponse
    {
        if (
            !isset($data['establishmentName'])
            || !isset($data['description'])
            || !isset($data['street'])
            || !isset($data['zipCode'])
            || !isset($data['city'])
        ) {
            return $this->jsonError('api.messages.missing_fields', Response::HTTP_BAD_REQUEST);
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

        return $this->validateLaundromatGraph($laundromat, $address);
    }

    public function createPlaceholderLogo(): Media
    {
        $logo = new Media();
        $logo->setLocation('/uploads/placeholders/laundry-logo.png');
        $logo->setOriginalName('placeholder-logo.png');
        $logo->setSize(0);
        $logo->setMimeType('image/png');

        return $logo;
    }

    private function validateLaundromatGraph(Laundromat $laundromat, Address $address): ?JsonResponse
    {
        $addressErrors = $this->validator->validate($address);
        if (count($addressErrors) > 0) {
            return $this->jsonError((string) $addressErrors, Response::HTTP_BAD_REQUEST);
        }

        $laundromatErrors = $this->validator->validate($laundromat);
        if (count($laundromatErrors) > 0) {
            return $this->jsonError((string) $laundromatErrors, Response::HTTP_BAD_REQUEST);
        }

        foreach ($laundromat->getClosures() as $closure) {
            $closureErrors = $this->validator->validate($closure);
            if (count($closureErrors) > 0) {
                return $this->jsonError((string) $closureErrors, Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($laundromat->getEquipments() as $equipment) {
            $equipmentErrors = $this->validator->validate($equipment);
            if (count($equipmentErrors) > 0) {
                return $this->jsonError((string) $equipmentErrors, Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($laundromat->getMedias() as $mediaRelation) {
            $mediaRelationErrors = $this->validator->validate($mediaRelation);
            if (count($mediaRelationErrors) > 0) {
                return $this->jsonError((string) $mediaRelationErrors, Response::HTTP_BAD_REQUEST);
            }

            $mediaErrors = $this->validator->validate($mediaRelation->getMedia());
            if (count($mediaErrors) > 0) {
                return $this->jsonError((string) $mediaErrors, Response::HTTP_BAD_REQUEST);
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
                ['day' => 'monday',    'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'tuesday',   'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'wednesday', 'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'thursday',  'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'friday',    'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'saturday',  'startTime' => '00:00', 'endTime' => '23:59'],
                ['day' => 'sunday',    'startTime' => '00:00', 'endTime' => '23:59'],
            ];
        }

        if (!is_array($openingHours) || $openingHours === []) {
            return $this->jsonError('api.messages.missing_fields', Response::HTTP_BAD_REQUEST);
        }

        foreach ($openingHours as $openingHour) {
            if (
                !is_array($openingHour)
                || !isset($openingHour['day'], $openingHour['startTime'], $openingHour['endTime'])
            ) {
                return $this->jsonError('api.messages.missing_fields', Response::HTTP_BAD_REQUEST);
            }

            $day = Day::tryFrom((string) $openingHour['day']);
            if (!$day instanceof Day) {
                return $this->jsonError('api.messages.invalid_schedule', Response::HTTP_BAD_REQUEST);
            }

            $startTime = \DateTimeImmutable::createFromFormat('H:i', (string) $openingHour['startTime']);
            $endTime = \DateTimeImmutable::createFromFormat('H:i', (string) $openingHour['endTime']);

            if (!$startTime instanceof \DateTimeImmutable || !$endTime instanceof \DateTimeImmutable || $startTime >= $endTime) {
                return $this->jsonError('api.messages.invalid_schedule', Response::HTTP_BAD_REQUEST);
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
                || !isset($machine['type'], $machine['capacity'], $machine['price'], $machine['duration'])
            ) {
                return $this->jsonError('api.messages.missing_fields', Response::HTTP_BAD_REQUEST);
            }

            $type = Equipment::tryFrom((string) $machine['type']);
            if (!$type instanceof Equipment) {
                return $this->jsonError('api.messages.invalid_equipment_type', Response::HTTP_BAD_REQUEST);
            }

            $capacity = (int) $machine['capacity'];
            $duration = (int) $machine['duration'];
            $price = number_format((float) $machine['price'], 2, '.', '');

            if ($capacity <= 0 || $duration <= 0 || (float) $price < 0) {
                return $this->jsonError('api.messages.invalid_equipment', Response::HTTP_BAD_REQUEST);
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
                return $this->jsonError('api.messages.invalid_photo', Response::HTTP_BAD_REQUEST);
            }

            if ($photo->getSize() !== null && $photo->getSize() > 5 * 1024 * 1024) {
                return $this->jsonError('api.messages.invalid_photo', Response::HTTP_BAD_REQUEST);
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

    private function jsonError(string $error, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $error], $status);
    }
}
