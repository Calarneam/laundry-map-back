<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Laundromat;
use App\Entity\LaundromatStatus;
use App\Entity\Media;
use App\Entity\PaymentMethod;
use App\Entity\Professional;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LaundromatFixture extends Fixture implements DependentFixtureInterface
{
    public const LAUNDROMAT_1 = 'laundromat-1';
    public const LAUNDROMAT_2 = 'laundromat-2';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $l1 = (new Laundromat())
            ->setProfessional($this->getReference(ProfessionalFixture::PRO_1, Professional::class))
            ->setStatus(LaundromatStatus::Validated)
            ->setAddress($this->getReference(AddressFixture::ADDRESS_1, Address::class))
            ->setLogo($this->getReference(MediaFixture::MEDIA_LOGO, Media::class))
            ->setEstablishmentName('Laverie du Centre')
            ->setContactEmail('contact@laverie-centre.fr')
            ->setDescription('Laverie self-service 24h/24.')
            ->setAddedDate($now)
            ->setUpdatedAt($now);
        $l1->addService($this->getReference(ServiceFixture::SERVICE_LAVERIE, Service::class));
        $l1->addService($this->getReference(ServiceFixture::SERVICE_REPASSAGE, Service::class));
        $l1->addPaymentMethod($this->getReference(PaymentMethodFixture::PAYMENT_CB, PaymentMethod::class));
        $l1->addPaymentMethod($this->getReference(PaymentMethodFixture::PAYMENT_ESPECES, PaymentMethod::class));

        $l2 = (new Laundromat())
            ->setProfessional($this->getReference(ProfessionalFixture::PRO_1, Professional::class))
            ->setStatus(LaundromatStatus::Pending)
            ->setAddress($this->getReference(AddressFixture::ADDRESS_2, Address::class))
            ->setEstablishmentName('Lav\'Express Lyon')
            ->setContactEmail('lyon@lavexpress.fr')
            ->setAddedDate($now)
            ->setUpdatedAt($now);
        $l2->addService($this->getReference(ServiceFixture::SERVICE_LAVERIE, Service::class));
        $l2->addPaymentMethod($this->getReference(PaymentMethodFixture::PAYMENT_ESPECES, PaymentMethod::class));

        $manager->persist($l1);
        $manager->persist($l2);
        $manager->flush();

        $this->addReference(self::LAUNDROMAT_1, $l1);
        $this->addReference(self::LAUNDROMAT_2, $l2);
    }

    public function getDependencies(): array
    {
        return [
            ProfessionalFixture::class,
            AddressFixture::class,
            MediaFixture::class,
            ServiceFixture::class,
            PaymentMethodFixture::class,
        ];
    }
}
