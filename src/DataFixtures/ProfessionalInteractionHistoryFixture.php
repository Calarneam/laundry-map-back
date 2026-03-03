<?php

namespace App\DataFixtures;

use App\Entity\Professional;
use App\Entity\ProfessionalInteractionHistory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProfessionalInteractionHistoryFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $history = (new ProfessionalInteractionHistory())
            ->setAdmin($this->getReference(UserFixture::USER_ADMIN, User::class))
            ->setProfessional($this->getReference(ProfessionalFixture::PRO_1, Professional::class))
            ->setAction('creation')
            ->setActionReason('Compte professionnel créé')
            ->setDate(new \DateTimeImmutable());

        $manager->persist($history);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, ProfessionalFixture::class];
    }
}
