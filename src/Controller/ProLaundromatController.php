<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Laundromat;
use App\Entity\LaundromatClosure;
use App\Entity\LaundromatEquipment;
use App\Entity\PaymentMethod;
use App\Entity\Professional;
use App\Entity\Service;
use App\Entity\Enum\Day;
use App\Entity\Enum\Equipment;
use App\Entity\Enum\GeolocationStatus;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Enum\ProfessionalStatus;
use App\Repository\LaundromatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pro/laundries', name: 'api_pro_laundries_')]
class ProLaundromatController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LaundromatRepository $laundromatRepository,
    ) {}

    private function getValidatedProfessional(): Professional|JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $professional = $user->getProfessional();

        if (!$professional || $professional->getStatus() !== ProfessionalStatus::Validated) {
            return $this->json(['error' => 'api.messages.forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $professional;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $professional = $this->getValidatedProfessional();
            if ($professional instanceof JsonResponse) {
                return $professional;
            }

            $laundromats = $professional->getLaundromats();

            $data = [];
            foreach ($laundromats as $laundromat) {
                $address = $laundromat->getAddress();
                $data[] = [
                    'id' => $laundromat->getId(),
                    'establishmentName' => $laundromat->getEstablishmentName(),
                    'status' => $laundromat->getStatus()?->value,
                    'address' => $address ? [
                        'city' => $address->getCity(),
                        'zipCode' => $address->getZipCode(),
                    ] : null,
                    'addedDate' => $laundromat->getAddedDate()?->format('Y-m-d'),
                    'updatedAt' => $laundromat->getUpdatedAt()?->format('Y-m-d'),
                ];
            }

            return $this->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $professional = $this->getValidatedProfessional();
            if ($professional instanceof JsonResponse) {
                return $professional;
            }

            $data = json_decode($request->getContent(), true);

            if (empty($data['establishmentName']) || empty($data['description']) || empty($data['address'])) {
                return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
            }

            $addressData = $data['address'];
            if (empty($addressData['address']) || empty($addressData['street']) || empty($addressData['zipCode'])
                || empty($addressData['city']) || empty($addressData['country'])) {
                return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
            }

            $address = new Address();
            $address->setAddress($addressData['address']);
            $address->setStreet($addressData['street']);
            $address->setZipCode((int) $addressData['zipCode']);
            $address->setCity($addressData['city']);
            $address->setCountry($addressData['country']);
            $address->setGeolocationStatus(GeolocationStatus::Pending);

            $laundromat = new Laundromat();
            $laundromat->setProfessional($professional);
            $laundromat->setEstablishmentName($data['establishmentName']);
            $laundromat->setDescription($data['description']);
            $laundromat->setContactEmail($data['contactEmail'] ?? null);
            $laundromat->setWiLineReference(isset($data['wiLineReference']) ? (int) $data['wiLineReference'] : null);
            $laundromat->setAddress($address);
            $laundromat->setStatus(LaundromatStatus::Pending);
            $laundromat->setAddedDate(new \DateTimeImmutable());
            $laundromat->setUpdatedAt(new \DateTimeImmutable());

            foreach ($data['serviceIds'] ?? [] as $serviceId) {
                $service = $this->entityManager->find(Service::class, (int) $serviceId);
                if ($service) {
                    $laundromat->getServices()->add($service);
                }
            }

            foreach ($data['paymentMethodIds'] ?? [] as $pmId) {
                $paymentMethod = $this->entityManager->find(PaymentMethod::class, (int) $pmId);
                if ($paymentMethod) {
                    $laundromat->getPaymentMethods()->add($paymentMethod);
                }
            }

            foreach ($data['equipments'] ?? [] as $equipmentData) {
                $equipment = $this->buildEquipment($equipmentData, $laundromat);
                if ($equipment instanceof JsonResponse) {
                    return $equipment;
                }
                $this->entityManager->persist($equipment);
            }

            foreach ($data['closures'] ?? [] as $closureData) {
                $closure = $this->buildClosure($closureData, $laundromat);
                if ($closure instanceof JsonResponse) {
                    return $closure;
                }
                $this->entityManager->persist($closure);
            }

            $this->entityManager->persist($laundromat);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'api.messages.laundromat_created',
                'id' => $laundromat->getId(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $professional = $this->getValidatedProfessional();
            if ($professional instanceof JsonResponse) {
                return $professional;
            }

            $laundromat = $this->laundromatRepository->find($id);
            if (!$laundromat) {
                return $this->json(['error' => 'api.messages.laundromat_not_found'], Response::HTTP_NOT_FOUND);
            }

            if ($laundromat->getProfessional()->getId() !== $professional->getId()) {
                return $this->json(['error' => 'api.messages.forbidden'], Response::HTTP_FORBIDDEN);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['establishmentName'])) {
                $laundromat->setEstablishmentName($data['establishmentName']);
            }
            if (isset($data['description'])) {
                $laundromat->setDescription($data['description']);
            }
            if (array_key_exists('contactEmail', $data)) {
                $laundromat->setContactEmail($data['contactEmail']);
            }
            if (array_key_exists('wiLineReference', $data)) {
                $laundromat->setWiLineReference($data['wiLineReference'] !== null ? (int) $data['wiLineReference'] : null);
            }

            if (isset($data['address'])) {
                $addressData = $data['address'];
                $address = $laundromat->getAddress() ?? new Address();
                if (isset($addressData['address'])) $address->setAddress($addressData['address']);
                if (isset($addressData['street'])) $address->setStreet($addressData['street']);
                if (isset($addressData['zipCode'])) $address->setZipCode((int) $addressData['zipCode']);
                if (isset($addressData['city'])) $address->setCity($addressData['city']);
                if (isset($addressData['country'])) $address->setCountry($addressData['country']);
                $address->setGeolocationStatus(GeolocationStatus::Pending);
                if ($laundromat->getAddress() === null) {
                    $laundromat->setAddress($address);
                }
            }

            if (array_key_exists('serviceIds', $data)) {
                $laundromat->getServices()->clear();
                foreach ($data['serviceIds'] as $serviceId) {
                    $service = $this->entityManager->find(Service::class, (int) $serviceId);
                    if ($service) {
                        $laundromat->getServices()->add($service);
                    }
                }
            }

            if (array_key_exists('paymentMethodIds', $data)) {
                $laundromat->getPaymentMethods()->clear();
                foreach ($data['paymentMethodIds'] as $pmId) {
                    $paymentMethod = $this->entityManager->find(PaymentMethod::class, (int) $pmId);
                    if ($paymentMethod) {
                        $laundromat->getPaymentMethods()->add($paymentMethod);
                    }
                }
            }

            if (array_key_exists('equipments', $data)) {
                foreach ($laundromat->getEquipments() as $old) {
                    $this->entityManager->remove($old);
                }
                $laundromat->getEquipments()->clear();
                foreach ($data['equipments'] as $equipmentData) {
                    $equipment = $this->buildEquipment($equipmentData, $laundromat);
                    if ($equipment instanceof JsonResponse) {
                        return $equipment;
                    }
                    $laundromat->getEquipments()->add($equipment);
                    $this->entityManager->persist($equipment);
                }
            }

            if (array_key_exists('closures', $data)) {
                foreach ($laundromat->getClosures() as $old) {
                    $this->entityManager->remove($old);
                }
                $laundromat->getClosures()->clear();
                foreach ($data['closures'] as $closureData) {
                    $closure = $this->buildClosure($closureData, $laundromat);
                    if ($closure instanceof JsonResponse) {
                        return $closure;
                    }
                    $laundromat->getClosures()->add($closure);
                    $this->entityManager->persist($closure);
                }
            }

            $laundromat->setStatus(LaundromatStatus::Pending);
            $laundromat->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            return $this->json(['message' => 'api.messages.laundromat_updated'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function buildEquipment(array $data, Laundromat $laundromat): LaundromatEquipment|JsonResponse
    {
        $type = Equipment::tryFrom($data['type'] ?? '');
        if (!$type) {
            return $this->json(['error' => 'api.messages.invalid_equipment_type'], Response::HTTP_BAD_REQUEST);
        }

        $equipment = new LaundromatEquipment();
        $equipment->setLaundromat($laundromat);
        $equipment->setName($data['name']);
        $equipment->setType($type);
        $equipment->setCapacity((int) $data['capacity']);
        $equipment->setPrice((string) $data['price']);
        $equipment->setDuration((int) $data['duration']);

        return $equipment;
    }

    private function buildClosure(array $data, Laundromat $laundromat): LaundromatClosure|JsonResponse
    {
        $day = Day::tryFrom($data['day'] ?? '');
        if (!$day) {
            return $this->json(['error' => 'api.messages.invalid_day'], Response::HTTP_BAD_REQUEST);
        }

        $startTime = \DateTimeImmutable::createFromFormat('H:i', $data['startTime'] ?? '');
        $endTime = \DateTimeImmutable::createFromFormat('H:i', $data['endTime'] ?? '');

        if (!$startTime || !$endTime) {
            return $this->json(['error' => 'api.messages.invalid_time_format'], Response::HTTP_BAD_REQUEST);
        }

        $now = new \DateTimeImmutable();
        $closure = new LaundromatClosure();
        $closure->setLaundromat($laundromat);
        $closure->setDay($day);
        $closure->setStartTime($startTime);
        $closure->setEndTime($endTime);
        $closure->setAddedDate($now);
        $closure->setUpdatedAt($now);

        return $closure;
    }
}
