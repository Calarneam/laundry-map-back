<?php

namespace App\DataFixtures;

use App\Entity\Laundromat;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatFavoriteFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->getReference(UserFixture::USER_1, User::class);
        $user2 = $this->getReference(UserFixture::USER_2, User::class);
        $laundromat = $this->getReference(LaundromatFixture::LAUNDROMAT_1, Laundromat::class);

        $user1->addFavoriteLaundromat($laundromat);
        $user2->addFavoriteLaundromat($laundromat);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, LaundromatFixture::class];
    }
}
