<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Professional;
use App\Entity\ProfessionalStatus;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProfessionalFixture extends Fixture implements DependentFixtureInterface
{
    public const PRO_1 = 'pro-1';

    public function load(ObjectManager $manager): void
    {
        $pro = (new Professional())
            ->setUser($this->getReference(UserFixture::USER_1, User::class))
            ->setSiren(123456789)
            ->setStatus(ProfessionalStatus::Pending)
            ->setAddress($this->getReference(AddressFixture::ADDRESS_1, Address::class));

        $manager->persist($pro);
        $manager->flush();

        $this->addReference(self::PRO_1, $pro);
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, AddressFixture::class];
    }
}
