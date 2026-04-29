<?php

namespace App\DataFixtures;

use App\Entity\Service;
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
        $wifi = new Service();
        $wifi->setName('Wi-Fi');
        $manager->persist($wifi);
        $this->addReference(self::REF_SERVICE_WIFI, $wifi);

        $parking = new Service();
        $parking->setName('Parking');
        $manager->persist($parking);
        $this->addReference(self::REF_SERVICE_PARKING, $parking);

        $lessive = new Service();
        $lessive->setName('Lessive');
        $manager->persist($lessive);
        $this->addReference(self::REF_SERVICE_LESSIVE, $lessive);

        $cb = new Service();
        $cb->setName('CB');
        $manager->persist($cb);
        $this->addReference(self::REF_SERVICE_CB, $cb);

        $accesPmr = new Service();
        $accesPmr->setName('Accès PMR');
        $manager->persist($accesPmr);
        $this->addReference(self::REF_SERVICE_ACCES_PMR, $accesPmr);

        $securite = new Service();
        $securite->setName('Sécurité');
        $manager->persist($securite);
        $this->addReference(self::REF_SERVICE_SECURITE, $securite);

        $manager->flush();
    }
}
