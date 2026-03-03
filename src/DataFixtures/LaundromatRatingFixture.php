<?php

namespace App\DataFixtures;

use App\Entity\Laundromat;
use App\Entity\LaundromatRating;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatRatingFixture extends Fixture implements DependentFixtureInterface
{
    public const RATING_1 = 'rating-1';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $rating = (new LaundromatRating())
            ->setLaundromat($this->getReference(LaundromatFixture::LAUNDROMAT_1, Laundromat::class))
            ->setUser($this->getReference(UserFixture::USER_2, User::class))
            ->setRating(5)
            ->setRatedAt($now)
            ->setComment('Très propre et machines récentes.')
            ->setCommentedAt($now);

        $manager->persist($rating);
        $manager->flush();

        $this->addReference(self::RATING_1, $rating);
    }

    public function getDependencies(): array
    {
        return [LaundromatFixture::class, UserFixture::class];
    }
}
