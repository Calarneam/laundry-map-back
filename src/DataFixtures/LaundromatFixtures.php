<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Enum\Day;
use App\Entity\Enum\Equipment;
use App\Entity\Enum\GeolocationStatus;
<<<<<<< Updated upstream
=======
use App\Entity\Enum\LaundromatExceptionalClosureType;
>>>>>>> Stashed changes
use App\Entity\Laundromat;
use App\Entity\LaundromatClosure;
use App\Entity\LaundromatEquipment;
use App\Entity\LaundromatExceptionalClosure;
use App\Entity\LaundromatMedia;
use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\ProfessionalFixtures;
use App\DataFixtures\ServiceFixtures;
use App\DataFixtures\PaymentMethodFixtures;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Professional;
use App\Entity\Service;
use App\Entity\PaymentMethod;

class LaundromatFixtures extends Fixture implements DependentFixtureInterface
{
    public const REF_LAUNDROMAT_1 = 'laundromat_1';

    public function load(ObjectManager $manager): void
    {
        $professional = $this->getReference(ProfessionalFixtures::REF_PRO_1, Professional::class);
        $serviceWifi = $this->getReference(ServiceFixtures::REF_SERVICE_WIFI, Service::class);
        $serviceParking = $this->getReference(ServiceFixtures::REF_SERVICE_PARKING, Service::class);
        $serviceLessive = $this->getReference(ServiceFixtures::REF_SERVICE_LESSIVE, Service::class);
        $serviceCb = $this->getReference(ServiceFixtures::REF_SERVICE_CB, Service::class);
        $serviceAccesPmr = $this->getReference(ServiceFixtures::REF_SERVICE_ACCES_PMR, Service::class);
        $serviceSecurite = $this->getReference(ServiceFixtures::REF_SERVICE_SECURITE, Service::class);
        $paymentCb = $this->getReference(PaymentMethodFixtures::REF_PAYMENT_CB, PaymentMethod::class);
        $paymentEspeces = $this->getReference(PaymentMethodFixtures::REF_PAYMENT_ESPECES, PaymentMethod::class);
        $paymentCoins = $this->getReference(PaymentMethodFixtures::REF_PAYMENT_COINS, PaymentMethod::class);

        $address = new Address();
        $address->setAddress('12 rue de la Soie');
        $address->setStreet('12 rue de la Soie');
        $address->setZipCode(75013);
        $address->setCity('Paris');
        $address->setCountry('FR');
<<<<<<< Updated upstream
        $address->setLattitude('48.8323');
        $address->setLongitude('2.3772');
=======
        $address->setPosition(Address::point(2.3772, 48.8323));
>>>>>>> Stashed changes
        $address->setGeolocationStatus(GeolocationStatus::Geolocated);
        $manager->persist($address);

        $logo = new Media();
        $logo->setLocation('/uploads/image.png');
        $logo->setOriginalName('logo.png');
        $logo->setSize(10240);
        $logo->setMimeType('image/png');
        $manager->persist($logo);

        $now = new \DateTimeImmutable();

        $laundromat = new Laundromat();
        $laundromat->setProfessional($professional);
        $laundromat->setAddress($address);
        $laundromat->setLogo($logo);
        $laundromat->setEstablishmentName('Laverie Express Paris 13');
        $laundromat->setContactEmail('contact@laverie-express-paris.fr');
        $laundromat->setContactPhone('01 45 83 22 10');
        $laundromat->setDescription('Laverie self-service ouverte 7j/7. Machines à laver et sèche-linge haute capacité. Paiement CB, pièces ou espèces. Accès PMR.');
        $laundromat->setAddedDate($now);
        $laundromat->setUpdatedAt($now);
        $laundromat->setStatus(LaundromatStatus::Validated);
        $laundromat->setWiLineReference('12345');

        $laundromat->getServices()->add($serviceWifi);
        $laundromat->getServices()->add($serviceParking);
        $laundromat->getServices()->add($serviceLessive);
        $laundromat->getServices()->add($serviceCb);
        $laundromat->getServices()->add($serviceAccesPmr);
        $laundromat->getServices()->add($serviceSecurite);
        $laundromat->getPaymentMethods()->add($paymentCb);
        $laundromat->getPaymentMethods()->add($paymentEspeces);
        $laundromat->getPaymentMethods()->add($paymentCoins);

        $manager->persist($laundromat);

        // Horaires d'ouverture (closures = plages horaires par jour) — 8h-20h tous les jours
        $days = [Day::Monday, Day::Tuesday, Day::Wednesday, Day::Thursday, Day::Friday, Day::Saturday, Day::Sunday];
        foreach ($days as $day) {
            $closure = new LaundromatClosure();
            $closure->setLaundromat($laundromat);
            $closure->setDay($day);
            $closure->setAddedDate($now);
            $closure->setUpdatedAt($now);
            $closure->setStartTime(new \DateTimeImmutable('08:00'));
            $closure->setEndTime(new \DateTimeImmutable('20:00'));
            $manager->persist($closure);
        }

        // Fermeture exceptionnelle (1 jour)
        $exceptional = new LaundromatExceptionalClosure();
        $exceptional->setLaundromat($laundromat);
        $exceptional->setStartDate($now->modify('+1 month'));
        $exceptional->setEndDate($now->modify('+1 month +1 day'));
        $exceptional->setType(LaundromatExceptionalClosureType::FullClosure);
        $exceptional->setReason('Travaux annuels');
        $exceptional->setAddedDate($now);
        $manager->persist($exceptional);

        // Équipements : lave-linges et sèche-linges
        $washers = [
            ['name' => 'Lave-linge 8 kg', 'capacity' => 8, 'price' => '5.00', 'duration' => 35],
            ['name' => 'Lave-linge 8 kg', 'capacity' => 8, 'price' => '5.00', 'duration' => 35],
            ['name' => 'Lave-linge 12 kg', 'capacity' => 12, 'price' => '7.00', 'duration' => 45],
            ['name' => 'Lave-linge 18 kg', 'capacity' => 18, 'price' => '10.00', 'duration' => 55],
        ];
        foreach ($washers as $i => $w) {
            $eq = new LaundromatEquipment();
            $eq->setLaundromat($laundromat);
            $eq->setName($w['name']);
            $eq->setType(Equipment::Washer);
            $eq->setCapacity($w['capacity']);
            $eq->setPrice($w['price']);
            $eq->setDuration($w['duration']);
            $eq->setEquipmentReference(1000 + $i);
            $manager->persist($eq);
        }

        $dryers = [
            ['name' => 'Sèche-linge 8 kg', 'capacity' => 8, 'price' => '3.00', 'duration' => 40],
            ['name' => 'Sèche-linge 8 kg', 'capacity' => 8, 'price' => '3.00', 'duration' => 40],
            ['name' => 'Sèche-linge 12 kg', 'capacity' => 12, 'price' => '4.50', 'duration' => 50],
        ];
        foreach ($dryers as $i => $d) {
            $eq = new LaundromatEquipment();
            $eq->setLaundromat($laundromat);
            $eq->setName($d['name']);
            $eq->setType(Equipment::Dryer);
            $eq->setCapacity($d['capacity']);
            $eq->setPrice($d['price']);
            $eq->setDuration($d['duration']);
            $eq->setEquipmentReference(2000 + $i);
            $manager->persist($eq);
        }

        // Média supplémentaire (photo intérieur)
        $mediaPhoto = new Media();
        $mediaPhoto->setLocation('/uploads/image.png');
        $mediaPhoto->setOriginalName('image.png');
        $mediaPhoto->setSize(256000);
        $mediaPhoto->setMimeType('image/png');
        $manager->persist($mediaPhoto);

        $laundromatMedia = new LaundromatMedia();
        $laundromatMedia->setLaundromat($laundromat);
        $laundromatMedia->setMedia($mediaPhoto);
        $laundromatMedia->setDescription('Espace self-service');
        $manager->persist($laundromatMedia);

        $this->addReference(self::REF_LAUNDROMAT_1, $laundromat);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProfessionalFixtures::class,
            ServiceFixtures::class,
            PaymentMethodFixtures::class,
        ];
    }
}
