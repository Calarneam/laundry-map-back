<?php

namespace App\Controller;

use App\Controller\AbstractApiController;
use App\Entity\Administrator;
use App\Entity\User;
use App\Entity\Enum\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function count;

#[Route('/api/user/profile', name: 'api_profile_')]
class ProfileController extends AbstractApiController
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/', name: 'get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $currentUser = $this->getUser();

        if ($currentUser->getRoles() === ['ROLE_ADMIN']) {
            return $this->json([
                'type' => UserType::Admin->value,
                'email' => $currentUser->getUserIdentifier(),
                'roles' => $currentUser->getRoles(),
            ]);
        }

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Unexpected authenticated user type'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = [
            'type' => UserType::User->value,
            'firstName' => $currentUser->getFirstName(),
            'lastName' => $currentUser->getLastName(),
            'email' => $currentUser->getUserIdentifier(),
            'roles' => $currentUser->getRoles(),
        ];

        $professional = $currentUser->getProfessional();
        if ($professional !== null) {
            $data['type'] = UserType::Pro->value;
            $data['siren'] = $professional->getSiren();
            $data['companyName'] = $professional->getCompanyName();
        }

        return $this->json($data);
    }

    #[Route('/password', name: 'update_password', methods: ['PATCH'])]
    public function updatePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['oldPassword']) || !isset($data['newPassword'])) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($currentUser, $data['oldPassword'])) {
            return $this->json(['error' => 'api.messages.invalid_old_password'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword($currentUser, $data['newPassword']);

        if ($currentUser instanceof Administrator || $currentUser instanceof User) {
            $currentUser->setPassword($hashedPassword);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'api.messages.password_changed',
        ], Response::HTTP_OK);
    }

    #[Route('/fullname', name: 'update_fullname', methods: ['PATCH'])]
    public function updateFullname(
        Request $request,
        ValidatorInterface $validator,
    ): JsonResponse {
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $currentUser->setFirstName($data['firstName']);
        $currentUser->setLastName($data['lastName']);

        $errors = $validator->validate($currentUser);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return $this->json(['error' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();
        
        return $this->json([
            'message' => 'api.messages.fullname_updated',
        ], Response::HTTP_OK);
    }

    #[Route('/', name: 'delete', methods: ['DELETE'])]
    public function deleteAccount(): JsonResponse
    {
        $currentUser = $this->getUser();
        
        $this->entityManager->remove($currentUser);
        $this->entityManager->flush();

        $response = new JsonResponse([
            'message' => 'api.messages.account_deleted',
        ], Response::HTTP_OK);

        $response->headers->clearCookie(
            'BEARER',
            '/',
            null,
            true,
            true,
            'lax'
        );
        $response->headers->clearCookie(
            'USER_ROLE',
            '/',
            null,
            true,
            false,
            'lax'
        );

        return $response;
    }

}