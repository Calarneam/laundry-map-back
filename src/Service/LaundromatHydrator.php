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
use App\Entity\PaymentMethod;
use App\Entity\Service;
use App\Repository\PaymentMethodRepository;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LaundromatHydrator
{
    private const MESSAGE_MISSING_FIELDS = 'api.messages.missing_fields';
    private const MESSAGE_INVALID_SCHEDULE = 'api.messages.invalid_schedule';
    private const MESSAGE_INVALID_EQUIPMENT_TYPE = 'api.messages.invalid_equipment_type';
    private const MESSAGE_INVALID_EQUIPMENT = 'api.messages.invalid_equipment';
    private const MESSAGE_INVALID_PHOTO = 'api.messages.invalid_photo';
    private const MESSAGE_INVALID_ADDRESS = 'api.messages.invalid_address';
    private const MESSAGE_INVALID_LAUNDROMAT = 'api.messages.invalid_laundromat';
    private const MESSAGE_INVALID_SERVICE = 'api.messages.invalid_service';
    private const MESSAGE_INVALID_PAYMENT_METHOD = 'api.messages.invalid_payment_method';
    private const MESSAGE_INVALID_LAUNDROMAT_CLOSURE = 'api.messages.invalid_laundromat_closure';
    private const MESSAGE_INVALID_LAUNDROMAT_EQUIPMENT = 'api.messages.invalid_laundromat_equipment';
    private const MESSAGE_INVALID_LAUNDROMAT_MEDIA = 'api.messages.invalid_laundromat_media';
    private const EQUIPMENT_WASHER_WITH_CAPACITY = 'api.equipment.washer_with_capacity';
    private const EQUIPMENT_DRYER_WITH_CAPACITY = 'api.equipment.dryer_with_capacity';
    private const MEDIA_LAUNDRY_PHOTO = 'api.media.laundry_photo';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly ServiceRepository $serviceRepository,
        private readonly PaymentMethodRepository $paymentMethodRepository,
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
            return $this->jsonError(self::MESSAGE_MISSING_FIELDS, Response::HTTP_BAD_REQUEST);
        }

        $address = $laundromat->getAddress() ?? new Address();
        $fullAddress = trim($data['street']) . ', ' . trim((string) $data['zipCode']) . ' ' . trim($data['city']) . ', ' . trim((string) ($data['country'] ?? 'France'));

        $address->setAddress($fullAddress);
        $address->setStreet(trim($data['street']));
        $address->setZipCode((int) $data['zipCode']);
        $address->setCity(trim($data['city']));
        $address->setCountry(trim((string) ($data['country'] ?? 'France')));

        $latitude = isset($data['latitude']) && is_numeric($data['latitude']) ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null;

        if (
            $latitude >= -90.0
            && $latitude <= 90.0
            && $longitude >= -180.0
            && $longitude <= 180.0
        ) {
            $address->setPosition(Address::point($longitude, $latitude));
            $address->setGeolocationStatus(GeolocationStatus::Geolocated);
        } else {
            $address->setPosition(null);
            $address->setGeolocationStatus(GeolocationStatus::Pending);
        }

        $now = new \DateTimeImmutable();

        $laundromat->setAddress($address);
        $laundromat->setEstablishmentName(trim($data['establishmentName']));
        $laundromat->setDescription(trim($data['description']));
        $laundromat->setContactEmail(isset($data['contactEmail']) && \is_string($data['contactEmail']) ? trim($data['contactEmail']) : null);
        $laundromat->setContactPhone(isset($data['contactPhone']) && \is_string($data['contactPhone']) ? trim($data['contactPhone']) : null);
        $laundromat->setUpdatedAt($now);

        if ($laundromat->getAddedDate() === null) {
            $laundromat->setAddedDate($now);
        }

        $wiLineClientCode = isset($data['wiLineClientCode']) && \is_string($data['wiLineClientCode'])
            ? trim($data['wiLineClientCode'])
            : '';
        $laundromat->setWiLineReference($wiLineClientCode !== '' ? $wiLineClientCode : null);

        $servicesError = $this->syncServices($laundromat, $data['services'] ?? []);
        if ($servicesError instanceof JsonResponse) {
            return $servicesError;
        }

        $paymentMethodsError = $this->syncPaymentMethods($laundromat, $data['paymentMethods'] ?? []);
        if ($paymentMethodsError instanceof JsonResponse) {
            return $paymentMethodsError;
        }

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
        $addressError = $this->validationErrorResponse($address, self::MESSAGE_INVALID_ADDRESS);
        if ($addressError instanceof JsonResponse) {
            return $addressError;
        }

        $laundromatError = $this->validationErrorResponse($laundromat, self::MESSAGE_INVALID_LAUNDROMAT);
        if ($laundromatError instanceof JsonResponse) {
            return $laundromatError;
        }

        foreach ($laundromat->getClosures() as $closure) {
            $closureError = $this->validationErrorResponse($closure, self::MESSAGE_INVALID_LAUNDROMAT_CLOSURE);
            if ($closureError instanceof JsonResponse) {
                return $closureError;
            }
        }

        foreach ($laundromat->getEquipments() as $equipment) {
            $equipmentError = $this->validationErrorResponse($equipment, self::MESSAGE_INVALID_LAUNDROMAT_EQUIPMENT);
            if ($equipmentError instanceof JsonResponse) {
                return $equipmentError;
            }
        }

        foreach ($laundromat->getMedias() as $mediaRelation) {
            $mediaRelationError = $this->validationErrorResponse($mediaRelation, self::MESSAGE_INVALID_LAUNDROMAT_MEDIA);
            if ($mediaRelationError instanceof JsonResponse) {
                return $mediaRelationError;
            }

            $mediaError = $this->validationErrorResponse($mediaRelation->getMedia(), self::MESSAGE_INVALID_PHOTO);
            if ($mediaError instanceof JsonResponse) {
                return $mediaError;
            }
        }

        return null;
    }

    private function syncServices(Laundromat $laundromat, mixed $services): ?JsonResponse
    {
        $laundromatServices = $laundromat->getServices();
        $laundromatServices?->clear();

        if (!\is_array($services)) {
            return $this->jsonError(self::MESSAGE_MISSING_FIELDS, Response::HTTP_BAD_REQUEST);
        }

        $serviceNames = [];
        foreach ($services as $serviceName) {
            if (!\is_string($serviceName) || trim($serviceName) === '') {
                continue;
            }
            $serviceNames[] = trim($serviceName);
        }

        $servicesByName = $this->serviceRepository->findIndexedByNames($serviceNames);

        foreach ($serviceNames as $normalizedServiceName) {
            $service = $servicesByName[$normalizedServiceName] ?? null;

            if (!$service instanceof Service) {
                return $this->jsonError(self::MESSAGE_INVALID_SERVICE, Response::HTTP_BAD_REQUEST);
            }

            $laundromatServices?->add($service);
        }

        return null;
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

        if (!\is_array($openingHours) || $openingHours === []) {
            return $this->jsonError(self::MESSAGE_MISSING_FIELDS, Response::HTTP_BAD_REQUEST);
        }

        foreach ($openingHours as $openingHour) {
            if (
                !\is_array($openingHour)
                || !isset($openingHour['day'], $openingHour['startTime'], $openingHour['endTime'])
            ) {
                return $this->jsonError(self::MESSAGE_MISSING_FIELDS, Response::HTTP_BAD_REQUEST);
            }

            $day = Day::tryFrom((string) $openingHour['day']);
            if (!$day instanceof Day) {
                return $this->jsonError(self::MESSAGE_INVALID_SCHEDULE, Response::HTTP_BAD_REQUEST);
            }

            $startTime = \DateTimeImmutable::createFromFormat('H:i', (string) $openingHour['startTime']);
            $endTime = \DateTimeImmutable::createFromFormat('H:i', (string) $openingHour['endTime']);

            if (!$startTime instanceof \DateTimeImmutable || !$endTime instanceof \DateTimeImmutable || $startTime >= $endTime) {
                return $this->jsonError(self::MESSAGE_INVALID_SCHEDULE, Response::HTTP_BAD_REQUEST);
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

    private function syncPaymentMethods(Laundromat $laundromat, mixed $paymentMethods): ?JsonResponse
    {
        $laundromatPaymentMethods = $laundromat->getPaymentMethods();
        $laundromatPaymentMethods?->clear();

        if (!\is_array($paymentMethods)) {
            return $this->jsonError(self::MESSAGE_MISSING_FIELDS, Response::HTTP_BAD_REQUEST);
        }

        $paymentMethodNames = [];
        foreach ($paymentMethods as $paymentMethodName) {
            if (!\is_string($paymentMethodName) || trim($paymentMethodName) === '') {
                continue;
            }
            $paymentMethodNames[] = trim($paymentMethodName);
        }

        $paymentMethodsByName = $this->paymentMethodRepository->findIndexedByNames($paymentMethodNames);

        foreach ($paymentMethodNames as $normalizedPaymentMethodName) {
            $paymentMethod = $paymentMethodsByName[$normalizedPaymentMethodName] ?? null;

            if (!$paymentMethod instanceof PaymentMethod) {
                return $this->jsonError(self::MESSAGE_INVALID_PAYMENT_METHOD, Response::HTTP_BAD_REQUEST);
            }

            $laundromatPaymentMethods?->add($paymentMethod);
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

        if (!\is_array($machines)) {
            return null;
        }

        foreach ($machines as $machine) {
            if (
                !\is_array($machine)
                || !isset($machine['type'], $machine['capacity'], $machine['price'], $machine['duration'])
            ) {
                return $this->jsonError(self::MESSAGE_MISSING_FIELDS, Response::HTTP_BAD_REQUEST);
            }

            $type = Equipment::tryFrom((string) $machine['type']);
            if (!$type instanceof Equipment) {
                return $this->jsonError(self::MESSAGE_INVALID_EQUIPMENT_TYPE, Response::HTTP_BAD_REQUEST);
            }

            $capacity = (int) $machine['capacity'];
            $duration = (int) $machine['duration'];
            $price = number_format((float) $machine['price'], 2, '.', '');

            if ($capacity <= 0 || $duration <= 0 || (float) $price < 0) {
                return $this->jsonError(self::MESSAGE_INVALID_EQUIPMENT, Response::HTTP_BAD_REQUEST);
            }

            $equipment = new LaundromatEquipment();
            $equipment->setLaundromat($laundromat);
            $equipment->setType($type);
            $equipment->setCapacity($capacity);
            $equipment->setPrice($price);
            $equipment->setDuration($duration);
            $equipment->setName($this->buildEquipmentName($type));

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
                return $this->jsonError(self::MESSAGE_INVALID_PHOTO, Response::HTTP_BAD_REQUEST);
            }

            if ($photo->getSize() !== null && $photo->getSize() > 5 * 1024 * 1024) {
                return $this->jsonError(self::MESSAGE_INVALID_PHOTO, Response::HTTP_BAD_REQUEST);
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
            $mediaRelation->setDescription(self::MEDIA_LAUNDRY_PHOTO);

            if ($index === 0 || !($laundromat->getLogo() instanceof Media) || !file_exists(dirname(__DIR__, 2) . '/public' . $laundromat->getLogo()->getLocation())) {
                $laundromat->setLogo($media);
            }

            $laundromat->getMedias()->add($mediaRelation);
            $this->entityManager->persist($mediaRelation);
        }

        return null;
    }

    private function buildEquipmentName(Equipment $type): string
    {
        if ($type === Equipment::Dryer) {
            return self::EQUIPMENT_DRYER_WITH_CAPACITY;
        }

        return self::EQUIPMENT_WASHER_WITH_CAPACITY;
    }

    private function validationErrorResponse(mixed $value, string $message): ?JsonResponse
    {
        $errors = $this->validator->validate($value);
        if (count($errors) === 0) {
            return null;
        }

        return $this->jsonError($message, Response::HTTP_BAD_REQUEST);
    }

    private function jsonError(string $error, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $error], $status);
    }
}
