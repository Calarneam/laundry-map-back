<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Enum\UserStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const REF_USER_1 = 'user_1';
    public const REF_USER_PRO = 'user_pro';
    public const REF_USER_2 = 'user_2';
    public const REF_USER_3 = 'user_3';

    public const PASSWORD_DEFAULT = 'test1234';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $user1 = new User();
        $user1->setEmail('user@laundrymap.com');
        $user1->setFirstName('John');
        $user1->setLastName('Doe');
        $user1->setStatus(UserStatus::Active);
        $user1->setPassword($this->passwordHasher->hashPassword($user1, self::PASSWORD_DEFAULT));
        $user1->setCreatedAt($now);
        $user1->setUpdatedAt($now);
        $manager->persist($user1);
        $this->addReference(self::REF_USER_1, $user1);

        $userPro = new User();
        $userPro->setEmail('pro@laundrymap.com');
        $userPro->setFirstName('Jane');
        $userPro->setLastName('Smith');
        $userPro->setStatus(UserStatus::Active);
        $userPro->setPassword($this->passwordHasher->hashPassword($userPro, self::PASSWORD_DEFAULT));
        $userPro->setCreatedAt($now);
        $userPro->setUpdatedAt($now);
        $manager->persist($userPro);
        $this->addReference(self::REF_USER_PRO, $userPro);

        $user2 = new User();
        $user2->setEmail('marie@laundrymap.com');
        $user2->setFirstName('Marie');
        $user2->setLastName('Martin');
        $user2->setStatus(UserStatus::Active);
        $user2->setPassword($this->passwordHasher->hashPassword($user2, self::PASSWORD_DEFAULT));
        $user2->setCreatedAt($now);
        $user2->setUpdatedAt($now);
        $manager->persist($user2);
        $this->addReference(self::REF_USER_2, $user2);

        $user3 = new User();
        $user3->setEmail('pierre@laundrymap.com');
        $user3->setFirstName('Pierre');
        $user3->setLastName('Durand');
        $user3->setStatus(UserStatus::Active);
        $user3->setPassword($this->passwordHasher->hashPassword($user3, self::PASSWORD_DEFAULT));
        $user3->setCreatedAt($now);
        $user3->setUpdatedAt($now);
        $manager->persist($user3);
        $this->addReference(self::REF_USER_3, $user3);

        $manager->flush();
    }
}
