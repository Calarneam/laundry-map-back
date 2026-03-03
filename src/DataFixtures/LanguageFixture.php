<?php

namespace App\DataFixtures;

use App\Entity\Language;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LanguageFixture extends Fixture
{
    public const LANG_FR = 'lang-fr';
    public const LANG_EN = 'lang-en';

    public function load(ObjectManager $manager): void
    {
        $fr = (new Language())->setName('Français')->setCode('fr');
        $en = (new Language())->setName('English')->setCode('en');

        $manager->persist($fr);
        $manager->persist($en);
        $manager->flush();

        $this->addReference(self::LANG_FR, $fr);
        $this->addReference(self::LANG_EN, $en);
    }
}
