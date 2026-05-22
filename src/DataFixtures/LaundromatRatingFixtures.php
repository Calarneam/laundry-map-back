<?php

namespace App\DataFixtures;

use App\Entity\Enum\LaundromatStatus;
use App\Entity\Enum\UserStatus;
use App\Entity\Laundromat;
use App\Entity\LaundromatRating;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Avis sur les laveries de démo (Paris + Senlis) pour alimenter averageRating sur la carte.
 */
class LaundromatRatingFixtures extends Fixture implements DependentFixtureInterface
{
    private const int REVIEWER_COUNT = 48;

    private const int MIN_RATINGS_PER_LAUNDROMAT = 6;

    private const int MAX_RATINGS_PER_LAUNDROMAT = 16;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(71_200);

        $now = new \DateTimeImmutable();
        $reviewers = $this->createReviewers($manager, $faker, $now);

        $laundromats = $manager->getRepository(Laundromat::class)->findBy([
            'status' => LaundromatStatus::Validated,
            'deletedAt' => null,
        ]);

        foreach ($laundromats as $index => $laundromat) {
            $this->seedRatingsForLaundromat($manager, $faker, $laundromat, $reviewers, $index, $now);
        }

        $manager->flush();
    }

    /**
     * @return list<User>
     */
    private function createReviewers(ObjectManager $manager, Generator $faker, \DateTimeImmutable $now): array
    {
        $reviewers = [
            $this->getReference(UserFixtures::REF_USER_1, User::class),
            $this->getReference(UserFixtures::REF_USER_2, User::class),
            $this->getReference(UserFixtures::REF_USER_3, User::class),
        ];

        for ($i = 0; $i < self::REVIEWER_COUNT; ++$i) {
            $user = new User();
            $user->setEmail(\sprintf('avis.review%d@laundrymap.fixture', $i + 1));
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setStatus(UserStatus::Active);
            $user->setPassword($this->passwordHasher->hashPassword($user, UserFixtures::PASSWORD_DEFAULT));
            $user->setCreatedAt($now);
            $user->setUpdatedAt($now);
            $manager->persist($user);
            $reviewers[] = $user;
        }

        return $reviewers;
    }

    /**
     * @param list<User> $reviewers
     */
    private function seedRatingsForLaundromat(
        ObjectManager $manager,
        Generator $faker,
        Laundromat $laundromat,
        array $reviewers,
        int $laundromatIndex,
        \DateTimeImmutable $now,
    ): void {
        $ratingCount = $faker->numberBetween(self::MIN_RATINGS_PER_LAUNDROMAT, self::MAX_RATINGS_PER_LAUNDROMAT);
        $ratingCount = min($ratingCount, \count($reviewers));

        /** @var list<User> $selectedReviewers */
        $selectedReviewers = $faker->randomElements($reviewers, $ratingCount);

        $comments = [
            'Machines propres et cycle rapide, je recommande.',
            'Un peu d’attente le samedi mais le personnel est sympa.',
            'Prix corrects pour le quartier, sèche-linge efficace.',
            'Accès facile, paiement CB sans souci.',
            'Espace un peu exigu mais tout fonctionne bien.',
            'Parfait pour une lessive express entre deux réunions.',
            'Manque un peu de produit lessive en distributeur.',
            'Très calme en semaine, idéal pour laver en paix.',
            'Quelques machines en panne mais globalement satisfait.',
            'Laverie bien entretenue, je reviendrai.',
        ];

        foreach ($selectedReviewers as $reviewerIndex => $reviewer) {
            $ratingValue = $this->pickRatingValue($faker, $laundromatIndex, $reviewerIndex);

            $ratedAt = $now->modify(\sprintf('-%d days', $faker->numberBetween(2, 400)));

            $laundromatRating = new LaundromatRating();
            $laundromatRating->setLaundromat($laundromat);
            $laundromatRating->setUser($reviewer);
            $laundromatRating->setRating($ratingValue);
            $laundromatRating->setRatedAt($ratedAt);

            if ($faker->boolean(72)) {
                $laundromatRating->setComment($faker->randomElement($comments));
                $laundromatRating->setCommentedAt($ratedAt);
            }

            if ($faker->boolean(18)) {
                $laundromatRating->setResponse('Merci pour votre retour, nous restons à votre écoute.');
                $laundromatRating->setRespondedAt($ratedAt->modify('+2 days'));
            }

            $manager->persist($laundromatRating);
        }
    }

    private function pickRatingValue(Generator $faker, int $laundromatIndex, int $reviewerIndex): int
    {
        $tier = ($laundromatIndex + (int) floor($reviewerIndex / 3)) % 5;

        return match ($tier) {
            0 => $faker->randomElement([5, 5, 5, 4, 4, 5]),
            1 => $faker->randomElement([4, 4, 5, 4, 3, 5]),
            2 => $faker->randomElement([3, 4, 4, 3, 4, 5]),
            3 => $faker->randomElement([2, 3, 3, 4, 3, 2]),
            default => $faker->randomElement([1, 2, 2, 3, 2, 1]),
        };
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            LaundromatFixtures::class,
            SenlisLaundromatFixtures::class,
        ];
    }
}
