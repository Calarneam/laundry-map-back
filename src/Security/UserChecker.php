<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Enum\UserStatus;
use App\Entity\Enum\ProfessionalStatus;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() === UserStatus::Banned) {
            throw new CustomUserMessageAccountStatusException('Your account has been banned.');
        }

        if ($user->getProfessional() !== null && $user->getProfessional()->getStatus() === ProfessionalStatus::Pending) {
            throw new CustomUserMessageAccountStatusException('Your professional account is pending.');
        }
    }

    public function checkPostAuth(UserInterface $user): void {}
}