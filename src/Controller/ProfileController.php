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

    #[Route('', name: 'get', methods: ['GET'])]
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
            'hasPassword' => $currentUser->getPassword() !== null,
            'hasOauth' => $currentUser->getOauthId() !== null,
            'avatarUrl' => $currentUser->getAvatarUrl(),
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

    #[Route('/set-password', name: 'set_password', methods: ['PATCH'])]
    public function setPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($currentUser->getPassword() !== null) {
            return $this->json(['error' => 'api.messages.password_already_set'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['newPassword'])) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword($currentUser, $data['newPassword']);
        $currentUser->setPassword($hashedPassword);
        $this->entityManager->flush();

        return $this->json(['message' => 'api.messages.password_changed'], Response::HTTP_OK);
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

    #[Route('/avatar', name: 'upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('avatar');
        if (!$file) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            return $this->json(['error' => 'api.messages.invalid_file_type'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            return $this->json(['error' => 'api.messages.file_too_large'], Response::HTTP_BAD_REQUEST);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Remove old avatar file if exists
        $oldUrl = $currentUser->getAvatarUrl();
        if ($oldUrl) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $oldUrl;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $filename = $currentUser->getId() . '_' . time() . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $avatarUrl = '/uploads/avatars/' . $filename;
        $currentUser->setAvatarUrl($avatarUrl);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'api.messages.avatar_updated',
            'avatarUrl' => $avatarUrl,
        ]);
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