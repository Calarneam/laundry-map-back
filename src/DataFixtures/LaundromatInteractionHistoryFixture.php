<?php

namespace App\DataFixtures;

use App\Entity\Laundromat;
use App\Entity\LaundromatInteractionHistory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatInteractionHistoryFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $history = (new LaundromatInteractionHistory())
            ->setAdmin($this->getReference(UserFixture::USER_ADMIN, User::class))
            ->setLaundromat($this->getReference(LaundromatFixture::LAUNDROMAT_1, Laundromat::class))
            ->setAction('validation')
            ->setActionReason('Établissement validé')
            ->setDate(new \DateTimeImmutable());

        $manager->persist($history);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, LaundromatFixture::class];
    }
}
