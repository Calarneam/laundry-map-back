<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserInteractionHistory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserInteractionHistoryFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $history = (new UserInteractionHistory())
            ->setAdmin($this->getReference(UserFixture::USER_ADMIN, User::class))
            ->setUser($this->getReference(UserFixture::USER_1, User::class))
            ->setAction('validation')
            ->setActionReason('Compte validé après vérification')
            ->setDate(new \DateTimeImmutable());

        $manager->persist($history);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}
