<?php

namespace App\DataFixtures;

use App\Entity\PaymentMethod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentMethodFixture extends Fixture
{
    public const PAYMENT_CB = 'payment-cb';
    public const PAYMENT_ESPECES = 'payment-especes';
    public const PAYMENT_APP = 'payment-app';

    public function load(ObjectManager $manager): void
    {
        $cb = (new PaymentMethod())->setName('Carte bancaire');
        $especes = (new PaymentMethod())->setName('Espèces');
        $app = (new PaymentMethod())->setName('Application mobile');

        $manager->persist($cb);
        $manager->persist($especes);
        $manager->persist($app);
        $manager->flush();

        $this->addReference(self::PAYMENT_CB, $cb);
        $this->addReference(self::PAYMENT_ESPECES, $especes);
        $this->addReference(self::PAYMENT_APP, $app);
    }
}
