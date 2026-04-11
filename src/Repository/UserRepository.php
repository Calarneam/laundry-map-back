<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Enum\ProfessionalStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findPendingProfessionals(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.professional', 'p')
            ->where('p.status = :status')
            ->setParameter('status', ProfessionalStatus::Pending)
            ->setParameter('newSince', new \DateTimeImmutable('-1 day'))
            ->select(
                "p.id, u.firstName, u.lastName, u.email, u.createdAt, p.siren, p.companyName,
                CASE WHEN u.createdAt >= :newSince THEN true ELSE false END AS isNew"
            )
            ->getQuery()
            ->getResult();
    }
}
