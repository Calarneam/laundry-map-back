<?php

namespace App\DataFixtures;

use App\Entity\OffensiveWord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OffensiveWordFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (['insulte1', 'spam', 'pub'] as $label) {
            $manager->persist((new OffensiveWord())->setLabel($label));
        }
        $manager->flush();
    }
}
