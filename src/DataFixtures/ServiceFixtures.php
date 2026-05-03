<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServiceFixtures extends Fixture
{
    public const REF_SERVICE_WIFI = 'service_wifi';
    public const REF_SERVICE_PARKING = 'service_parking';
    public const REF_SERVICE_LESSIVE = 'service_lessive';
    public const REF_SERVICE_CB = 'service_cb';
    public const REF_SERVICE_ACCES_PMR = 'service_acces_pmr';
    public const REF_SERVICE_SECURITE = 'service_securite';

    public function load(ObjectManager $manager): void
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $manager->getRepository(Service::class);

        $services = [
            self::REF_SERVICE_WIFI => 'Wi-Fi',
            self::REF_SERVICE_PARKING => 'Parking',
            self::REF_SERVICE_LESSIVE => 'Lessive',
            self::REF_SERVICE_CB => 'CB',
            self::REF_SERVICE_ACCES_PMR => 'Accès PMR',
            self::REF_SERVICE_SECURITE => 'Sécurité',
        ];

        foreach ($services as $reference => $name) {
            $service = $serviceRepository->findOneBy(['name' => $name]);
            if (!$service instanceof Service) {
                $service = new Service();
                $service->setName($name);
                $manager->persist($service);
            }

            $this->addReference($reference, $service);
        }

        $manager->flush();
    }
}
