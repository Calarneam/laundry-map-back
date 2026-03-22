<?php

namespace App\DataFixtures;

use App\Entity\Language;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LanguageFixtures extends Fixture
{
    public const REF_LANG_FR = 'language_fr';
    public const REF_LANG_EN = 'language_en';

    public function load(ObjectManager $manager): void
    {
        $fr = new Language();
        $fr->setCode('fr');
        $fr->setName('Français');
        $manager->persist($fr);
        $this->addReference(self::REF_LANG_FR, $fr);

        $en = new Language();
        $en->setCode('en');
        $en->setName('English');
        $manager->persist($en);
        $this->addReference(self::REF_LANG_EN, $en);

        $manager->flush();
    }
}
