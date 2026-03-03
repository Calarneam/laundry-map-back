<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    public const USER_1 = 'user-1';
    public const USER_2 = 'user-2';
    public const USER_ADMIN = 'user-admin';

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $u1 = (new User())
            ->setEmail('jean.dupont@example.com')
            ->setFirstName('Jean')
            ->setLastName('Dupont')
            ->setStatus(UserStatus::Validated)
            ->setRoles(['ROLE_USER'])
            ->setCreatedAt($now)
            ->setUpdatedAt($now);
        $u1->setPassword($this->hasher->hashPassword($u1, 'password123'));

        $u2 = (new User())
            ->setEmail('marie.martin@example.com')
            ->setFirstName('Marie')
            ->setLastName('Martin')
            ->setStatus(UserStatus::Validated)
            ->setRoles(['ROLE_USER'])
            ->setCreatedAt($now)
            ->setUpdatedAt($now);
        $u2->setPassword($this->hasher->hashPassword($u2, 'password123'));

        $admin = (new User())
            ->setEmail('admin@laundromat.com')
            ->setFirstName('Admin')
            ->setLastName('System')
            ->setStatus(UserStatus::Validated)
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->setCreatedAt($now)
            ->setUpdatedAt($now);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));

        $manager->persist($u1);
        $manager->persist($u2);
        $manager->persist($admin);
        $manager->flush();

        $this->addReference(self::USER_1, $u1);
        $this->addReference(self::USER_2, $u2);
        $this->addReference(self::USER_ADMIN, $admin);
    }
}
