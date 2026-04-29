<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Enum\Day;
use App\Entity\Enum\Equipment;
use App\Entity\Enum\GeolocationStatus;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Laundromat;
use App\Entity\LaundromatClosure;
use App\Entity\LaundromatEquipment;
use App\Entity\Media;
use App\Entity\PaymentMethod;
use App\Entity\Professional;
use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

/**
 * ~50 laveries fictives à Senlis (Faker fr_FR, coordonnées dans l’agglomération).
 */
class SenlisLaundromatFixtures extends Fixture implements DependentFixtureInterface
{
    public const COUNT = 50;

    /** Première laverie générée (tests / autres fixtures). */
    public const REF_LAUNDROMAT_SENLIS_1 = 'laundromat_senlis_1';

    private const SENLIS_CENTER_LAT = 49.2083;
    private const SENLIS_CENTER_LNG = 2.5875;

    /** ~1,5 km autour du centre pour rester crédible sur la carte. */
    private const LAT_SPREAD = 0.014;
    private const LNG_SPREAD = 0.018;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(60_300);

        $professional1 = $this->getReference(ProfessionalFixtures::REF_PRO_1, Professional::class);
        $professional2 = $this->getReference(ProfessionalFixtures::REF_PRO_2, Professional::class);

        $serviceLavage = $this->getReference(ServiceFixtures::REF_SERVICE_LAVAGE, Service::class);
        $serviceSechage = $this->getReference(ServiceFixtures::REF_SERVICE_SECHAGE, Service::class);
        $serviceRepassage = $this->getReference(ServiceFixtures::REF_SERVICE_REPASSAGE, Service::class);

        $paymentCb = $this->getReference(PaymentMethodFixtures::REF_PAYMENT_CB, PaymentMethod::class);
        $paymentEspeces = $this->getReference(PaymentMethodFixtures::REF_PAYMENT_ESPECES, PaymentMethod::class);
        $paymentCoins = $this->getReference(PaymentMethodFixtures::REF_PAYMENT_COINS, PaymentMethod::class);

        $now = new \DateTimeImmutable();

        for ($i = 0; $i < self::COUNT; ++$i) {
            $professional = $i % 3 === 0 ? $professional2 : $professional1;

            $streetNumber = $faker->numberBetween(1, 140);
            $streetName = $faker->streetName();
            $street = $streetNumber.' '.$streetName;
            $addressLine = $street.', 60300 Senlis';

            $lat = (string) round(
                self::SENLIS_CENTER_LAT + $faker->randomFloat(6, -self::LAT_SPREAD, self::LAT_SPREAD),
                5
            );
            $lng = (string) round(
                self::SENLIS_CENTER_LNG + $faker->randomFloat(6, -self::LNG_SPREAD, self::LNG_SPREAD),
                5
            );

            $prefixes = ['Laverie ', 'Wash Express ', 'Self Lavage ', 'Clean & Go ', 'Libre-service '];
            $establishmentName = $faker->randomElement($prefixes).$faker->company();

            $site = [
                'name' => mb_substr($establishmentName, 0, 255),
                'street' => mb_substr($street, 0, 255),
                'address' => mb_substr($addressLine, 0, 255),
                'lat' => $lat,
                'lng' => $lng,
                'email' => $faker->unique()->safeEmail(),
                'wiLine' => 40_000 + $i,
                'description' => $this->truncateDescription($faker->realText(380)),
                'addRepassage' => $faker->boolean(35),
                'addCoins' => $faker->boolean(45),
                'openStart' => sprintf('%02d:%02d', $faker->numberBetween(7, 9), $faker->randomElement([0, 30])),
                'openEnd' => sprintf('%02d:%02d', $faker->numberBetween(20, 22), $faker->randomElement([0, 30])),
            ];

            $this->persistSenlisSite(
                $manager,
                $professional,
                $serviceLavage,
                $serviceSechage,
                $serviceRepassage,
                $paymentCb,
                $paymentEspeces,
                $paymentCoins,
                $now,
                $faker,
                $site,
                $i === 0 ? self::REF_LAUNDROMAT_SENLIS_1 : null,
            );
        }

        $manager->flush();
    }

    private function truncateDescription(string $text): string
    {
        if (mb_strlen($text) <= 570) {
            return $text;
        }

        return mb_substr($text, 0, 567).'…';
    }

    /**
     * @param array{
     *     name: string,
     *     street: string,
     *     address: string,
     *     lat: string,
     *     lng: string,
     *     email: string,
     *     wiLine: int,
     *     description: string,
     *     addRepassage: bool,
     *     addCoins: bool,
     *     openStart: string,
     *     openEnd: string,
     * } $site
     */
    private function persistSenlisSite(
        ObjectManager $manager,
        Professional $professional,
        Service $serviceLavage,
        Service $serviceSechage,
        Service $serviceRepassage,
        PaymentMethod $paymentCb,
        PaymentMethod $paymentEspeces,
        PaymentMethod $paymentCoins,
        \DateTimeImmutable $now,
        Generator $faker,
        array $site,
        ?string $referenceName = null,
    ): void {
        $address = new Address();
        $address->setAddress($site['address']);
        $address->setStreet($site['street']);
        $address->setZipCode(60300);
        $address->setCity('Senlis');
        $address->setCountry('FR');
        $address->setLattitude($site['lat']);
        $address->setLongitude($site['lng']);
        $address->setGeolocationStatus(GeolocationStatus::Geolocated);
        $manager->persist($address);

        $logo = new Media();
        $logo->setLocation('/uploads/fixtures/logo-senlis-'.$site['wiLine'].'.png');
        $logo->setOriginalName('logo-'.$site['wiLine'].'.png');
        $logo->setSize($site['wiLine'] % 9_000 + 4_096);
        $logo->setMimeType('image/png');
        $manager->persist($logo);

        $laundromat = new Laundromat();
        $laundromat->setProfessional($professional);
        $laundromat->setAddress($address);
        $laundromat->setLogo($logo);
        $laundromat->setEstablishmentName($site['name']);
        $laundromat->setContactEmail($site['email']);
        $laundromat->setDescription($site['description']);
        $laundromat->setAddedDate($now);
        $laundromat->setUpdatedAt($now);
        $laundromat->setStatus(LaundromatStatus::Validated);
        $laundromat->setWiLineReference($site['wiLine']);

        $laundromat->getServices()->add($serviceLavage);
        $laundromat->getServices()->add($serviceSechage);
        if ($site['addRepassage']) {
            $laundromat->getServices()->add($serviceRepassage);
        }

        $laundromat->getPaymentMethods()->add($paymentCb);
        $laundromat->getPaymentMethods()->add($paymentEspeces);
        if ($site['addCoins']) {
            $laundromat->getPaymentMethods()->add($paymentCoins);
        }

        $manager->persist($laundromat);

        $days = [Day::Monday, Day::Tuesday, Day::Wednesday, Day::Thursday, Day::Friday, Day::Saturday, Day::Sunday];
        foreach ($days as $day) {
            $closure = new LaundromatClosure();
            $closure->setLaundromat($laundromat);
            $closure->setDay($day);
            $closure->setAddedDate($now);
            $closure->setUpdatedAt($now);
            $closure->setStartTime(new \DateTimeImmutable($site['openStart']));
            $closure->setEndTime(new \DateTimeImmutable($site['openEnd']));
            $manager->persist($closure);
        }

        $washerCap = $faker->numberBetween(8, 12);
        $washer = new LaundromatEquipment();
        $washer->setLaundromat($laundromat);
        $washer->setName('Lave-linge '.$washerCap.' kg');
        $washer->setType(Equipment::Washer);
        $washer->setCapacity($washerCap);
        $washer->setPrice(number_format($faker->randomFloat(2, 3.5, 6.5), 2, '.', ''));
        $washer->setDuration($faker->numberBetween(32, 48));
        $washer->setEquipmentReference($site['wiLine'] * 100 + 1);
        $manager->persist($washer);

        $dryerCap = $faker->numberBetween(8, 12);
        $dryer = new LaundromatEquipment();
        $dryer->setLaundromat($laundromat);
        $dryer->setName('Sèche-linge '.$dryerCap.' kg');
        $dryer->setType(Equipment::Dryer);
        $dryer->setCapacity($dryerCap);
        $dryer->setPrice(number_format($faker->randomFloat(2, 2.5, 4.5), 2, '.', ''));
        $dryer->setDuration($faker->numberBetween(30, 42));
        $dryer->setEquipmentReference($site['wiLine'] * 100 + 2);
        $manager->persist($dryer);

        if (null !== $referenceName) {
            $this->addReference($referenceName, $laundromat);
        }
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
