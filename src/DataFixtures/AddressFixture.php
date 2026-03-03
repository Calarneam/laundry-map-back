<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\GeolocationStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AddressFixture extends Fixture
{
    public const ADDRESS_1 = 'address-1';
    public const ADDRESS_2 = 'address-2';
    public const ADDRESS_3 = 'address-3';

    public function load(ObjectManager $manager): void
    {
        $a1 = (new Address())
            ->setAddress('123 Rue de la Lavande')
            ->setStreet('Rue de la Lavande')
            ->setZipCode(75001)
            ->setCity('Paris')
            ->setCountry('France')
            ->setLatitude(48.8566)
            ->setLongitude(2.3522)
            ->setGeolocationStatus(GeolocationStatus::Geolocated);

        $a2 = (new Address())
            ->setAddress('45 Avenue des Lessives')
            ->setStreet('Avenue des Lessives')
            ->setZipCode(69001)
            ->setCity('Lyon')
            ->setCountry('France')
            ->setLatitude(45.7640)
            ->setLongitude(4.8357)
            ->setGeolocationStatus(GeolocationStatus::Geolocated);

        $a3 = (new Address())
            ->setAddress('7 Place du Linge')
            ->setStreet('Place du Linge')
            ->setZipCode(33000)
            ->setCity('Bordeaux')
            ->setCountry('France')
            ->setGeolocationStatus(GeolocationStatus::Pending);

        $manager->persist($a1);
        $manager->persist($a2);
        $manager->persist($a3);
        $manager->flush();

        $this->addReference(self::ADDRESS_1, $a1);
        $this->addReference(self::ADDRESS_2, $a2);
        $this->addReference(self::ADDRESS_3, $a3);
    }
}
