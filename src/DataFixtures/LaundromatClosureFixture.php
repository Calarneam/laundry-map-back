<?php

namespace App\DataFixtures;

use App\Entity\ClosureDay;
use App\Entity\Laundromat;
use App\Entity\LaundromatClosure;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatClosureFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $closure = (new LaundromatClosure())
            ->setLaundromat($this->getReference(LaundromatFixture::LAUNDROMAT_1, Laundromat::class))
            ->setDay(ClosureDay::Sunday)
            ->setAddedDate($now)
            ->setUpdatedAt($now)
            ->setStartTime(new \DateTime('00:00:00'))
            ->setEndTime(new \DateTime('23:59:59'));

        $manager->persist($closure);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LaundromatFixture::class];
    }
}
