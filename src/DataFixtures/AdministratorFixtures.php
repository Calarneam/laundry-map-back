<?php

namespace App\DataFixtures;

use App\Entity\Administrator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdministratorFixtures extends Fixture
{
    public const REF_ADMIN = 'admin_1';
    public const EMAIL_ADMIN = 'admin@laundrymap.com';
    public const PASSWORD_DEFAULT = 'test1234';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $administrator = new Administrator();
        $administrator->setEmail(self::EMAIL_ADMIN);
        $administrator->setPassword($this->passwordHasher->hashPassword($administrator, self::PASSWORD_DEFAULT));
        $manager->persist($administrator);
        $this->addReference(self::REF_ADMIN, $administrator);
        $manager->flush();
    }
}