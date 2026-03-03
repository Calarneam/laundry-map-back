<?php

namespace App\DataFixtures;

use App\Entity\Laundromat;
use App\Entity\LaundromatMedia;
use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatMediaFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $lm = (new LaundromatMedia())
            ->setLaundromat($this->getReference(LaundromatFixture::LAUNDROMAT_1, Laundromat::class))
            ->setMedia($this->getReference(MediaFixture::MEDIA_PHOTO, Media::class))
            ->setDescription('Vue intérieure de la laverie');

        $manager->persist($lm);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [LaundromatFixture::class, MediaFixture::class];
    }
}
