<?php

namespace App\DataFixtures;

use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServiceFixture extends Fixture
{
    public const SERVICE_LAVERIE = 'service-laverie';
    public const SERVICE_REPASSAGE = 'service-repassage';
    public const SERVICE_LIVRAISON = 'service-livraison';

    public function load(ObjectManager $manager): void
    {
        $s1 = (new Service())->setName('Laverie');
        $s2 = (new Service())->setName('Repassage');
        $s3 = (new Service())->setName('Livraison');

        $manager->persist($s1);
        $manager->persist($s2);
        $manager->persist($s3);
        $manager->flush();

        $this->addReference(self::SERVICE_LAVERIE, $s1);
        $this->addReference(self::SERVICE_REPASSAGE, $s2);
        $this->addReference(self::SERVICE_LIVRAISON, $s3);
    }
}
