<?php

namespace App\DataFixtures;

use App\Entity\PaymentMethod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentMethodFixtures extends Fixture
{
    public const REF_PAYMENT_CB = 'payment_cb';
    public const REF_PAYMENT_ESPECES = 'payment_especes';
    public const REF_PAYMENT_COINS = 'payment_coins';

    public function load(ObjectManager $manager): void
    {
        $cb = new PaymentMethod();
        $cb->setName('Carte bancaire');
        $manager->persist($cb);
        $this->addReference(self::REF_PAYMENT_CB, $cb);

        $especes = new PaymentMethod();
        $especes->setName('Espèces');
        $manager->persist($especes);
        $this->addReference(self::REF_PAYMENT_ESPECES, $especes);

        $coins = new PaymentMethod();
        $coins->setName('Pièces');
        $manager->persist($coins);
        $this->addReference(self::REF_PAYMENT_COINS, $coins);

        $manager->flush();
    }
}
