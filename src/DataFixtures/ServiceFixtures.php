<?php

namespace App\DataFixtures;

use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServiceFixtures extends Fixture
{
    public const REF_SERVICE_LAVAGE = 'service_lavage';
    public const REF_SERVICE_SECHAGE = 'service_sechage';
    public const REF_SERVICE_REPASSAGE = 'service_repassage';

    public function load(ObjectManager $manager): void
    {
        $lavage = new Service();
        $lavage->setName('Lavage');
        $manager->persist($lavage);
        $this->addReference(self::REF_SERVICE_LAVAGE, $lavage);

        $sechage = new Service();
        $sechage->setName('Séchage');
        $manager->persist($sechage);
        $this->addReference(self::REF_SERVICE_SECHAGE, $sechage);

        $repassage = new Service();
        $repassage->setName('Repassage');
        $manager->persist($repassage);
        $this->addReference(self::REF_SERVICE_REPASSAGE, $repassage);

        $manager->flush();
    }
}
