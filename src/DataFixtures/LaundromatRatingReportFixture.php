<?php

namespace App\DataFixtures;

use App\Entity\LaundromatRating;
use App\Entity\LaundromatRatingReport;
use App\Entity\ReportReason;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatRatingReportFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $report = (new LaundromatRatingReport())
            ->setLaundromatRating($this->getReference(LaundromatRatingFixture::RATING_1, LaundromatRating::class))
            ->setUser($this->getReference(UserFixture::USER_1, User::class))
            ->setDate(new \DateTimeImmutable())
            ->setReason(ReportReason::Spam)
            ->setComment('Signalement test');

        $manager->persist($report);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LaundromatRatingFixture::class, UserFixture::class];
    }
}
