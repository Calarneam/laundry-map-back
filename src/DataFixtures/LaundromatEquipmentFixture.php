<?php

namespace App\DataFixtures;

use App\Entity\EquipmentType;
use App\Entity\Laundromat;
use App\Entity\LaundromatEquipment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatEquipmentFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $laundromat = $this->getReference(LaundromatFixture::LAUNDROMAT_1, Laundromat::class);

        $washer = (new LaundromatEquipment())
            ->setLaundromat($laundromat)
            ->setName('Machine 8 kg')
            ->setType(EquipmentType::Washer)
            ->setCapacity(8000)
            ->setPrice(5.0)
            ->setDuration(35);

        $dryer = (new LaundromatEquipment())
            ->setLaundromat($laundromat)
            ->setName('Sèche-linge 9 kg')
            ->setType(EquipmentType::Dryer)
            ->setCapacity(9000)
            ->setPrice(3.0)
            ->setDuration(45);

        $manager->persist($washer);
        $manager->persist($dryer);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LaundromatFixture::class];
    }
}
