<?php

namespace App\DataFixtures;

use App\Entity\Language;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Entity\UserPreferenceTheme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserPreferenceFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $p1 = (new UserPreference())
            ->setUser($this->getReference(UserFixture::USER_1, User::class))
            ->setLanguage($this->getReference(LanguageFixture::LANG_FR, Language::class))
            ->setTheme(UserPreferenceTheme::System)
            ->setNotifications(true);

        $p2 = (new UserPreference())
            ->setUser($this->getReference(UserFixture::USER_2, User::class))
            ->setLanguage($this->getReference(LanguageFixture::LANG_EN, Language::class))
            ->setTheme(UserPreferenceTheme::Dark)
            ->setNotifications(false);

        $manager->persist($p1);
        $manager->persist($p2);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class, LanguageFixture::class];
    }
}
