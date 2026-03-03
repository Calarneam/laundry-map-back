<?php

namespace App\DataFixtures;

use App\Entity\Laundromat;
use App\Entity\LaundromatExceptionalClosure;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatExceptionalClosureFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $closure = (new LaundromatExceptionalClosure())
            ->setLaundromat($this->getReference(LaundromatFixture::LAUNDROMAT_1, Laundromat::class))
            ->setStartDate($now->modify('+1 week'))
            ->setEndDate($now->modify('+1 week +1 day'))
            ->setReason('Travaux de maintenance')
            ->setAddedDate($now);

        $manager->persist($closure);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LaundromatFixture::class];
    }
}
