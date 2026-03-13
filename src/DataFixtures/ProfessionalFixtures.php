<?php

namespace App\DataFixtures;

use App\Entity\Professional;
use App\Entity\Enum\ProfessionalStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\UserFixtures;
use App\Entity\User;

class ProfessionalFixtures extends Fixture implements DependentFixtureInterface
{
    public const REF_PRO_1 = 'professional_1';
    public const REF_PRO_2 = 'professional_2';

    public function load(ObjectManager $manager): void
    {
        $userPro = $this->getReference(UserFixtures::REF_USER_PRO, User::class);
        $user2 = $this->getReference(UserFixtures::REF_USER_2, User::class);

        $professional1 = new Professional();
        $professional1->setUser($userPro);
        $professional1->setSiren(123456789);
        $professional1->setStatus(ProfessionalStatus::Validated);
        $professional1->setValidationDate(new \DateTimeImmutable());
        $manager->persist($professional1);
        $userPro->setProfessional($professional1);
        $this->addReference(self::REF_PRO_1, $professional1);

        $professional2 = new Professional();
        $professional2->setUser($user2);
        $professional2->setSiren(987654321);
        $professional2->setStatus(ProfessionalStatus::Pending);
        $manager->persist($professional2);
        $user2->setProfessional($professional2);
        $this->addReference(self::REF_PRO_2, $professional2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
