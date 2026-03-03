<?php

namespace App\DataFixtures;

use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MediaFixture extends Fixture
{
    public const MEDIA_LOGO = 'media-logo';
    public const MEDIA_PHOTO = 'media-photo';

    public function load(ObjectManager $manager): void
    {
        $logo = (new Media())
            ->setLocation('/uploads/logo1.png')
            ->setOriginalName('logo-laundromat.png')
            ->setSize(15360)
            ->setMimeType('image/png');

        $photo = (new Media())
            ->setLocation('/uploads/photo1.jpg')
            ->setOriginalName('interieur.jpg')
            ->setSize(25600)
            ->setMimeType('image/jpeg');

        $manager->persist($logo);
        $manager->persist($photo);
        $manager->flush();

        $this->addReference(self::MEDIA_LOGO, $logo);
        $this->addReference(self::MEDIA_PHOTO, $photo);
    }
}
