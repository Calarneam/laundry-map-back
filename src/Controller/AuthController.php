<?php

namespace App\Controller;

use App\Entity\Administrator;
use App\Entity\User;
use App\Entity\Professional;
use App\Entity\Address;
use App\Entity\Enum\UserStatus;
use App\Entity\Enum\ProfessionalStatus;
use App\Entity\Enum\UserType;
use App\Entity\Enum\GeolocationStatus;
use App\Repository\UserRepository;
use App\Repository\ProfessionalRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\AbstractApiController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractApiController
{
    #[Route('/auth/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json([
                    'error' => 'api.messages.missing_fields'
                ], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return $this->json([
                    'error' => 'api.messages.email_already_used'
                ], Response::HTTP_CONFLICT);
            }

            $user = new User();

            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setEmail($data['email']);

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data['password']
            );
            $user->setPassword($hashedPassword);
            $user->setStatus(UserStatus::Active);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'message' => 'api.messages.user_created_successfully',
                'user' => [
                    'email' => $user->getUserIdentifier(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'roles' => $user->getRoles()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function SirenIsExisting(string $siren, HttpClientInterface $httpClient): bool
    {
        $response = $httpClient->request('GET', 'https://recherche-entreprises.api.gouv.fr/search?q=' . $siren);
        $data = $response->toArray();
        if ($data['total_results'] > 0) {
            return true;
        }

        return false;
    }

    #[Route('/auth/register/professional', name: 'register_professional', methods: ['POST'])]
    public function registerProfessional(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        ProfessionalRepository $professionalRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        HttpClientInterface $httpClient
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json([
                    'error' => 'api.messages.missing_fields'
                ], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return $this->json([
                    'error' => 'api.messages.email_already_used'
                ], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $professional = new Professional();

            $user->setProfessional($professional);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setEmail($data['email']);

            $existingProfessional = $professionalRepository->findOneBy(['siren' => $data['siren']]);
            if (!$this->SirenIsExisting($data['siren'], $httpClient) || $existingProfessional !== null) {
                return $this->json([
                    'error' => 'api.messages.siren_not_found_or_already_used'
                ], Response::HTTP_BAD_REQUEST);
            }

            $professional->setSiren($data['siren']);
            $professional->setCompanyName($data['companyName']);
            $professional->setCodeApe($data['codeApe']);
            $professional->setStatus(ProfessionalStatus::Pending);

            $address = new Address();
            $fullAddress = $data['street'] . ', ' . $data['zipCode'] . ' ' . $data['city'] . ', ' . $data['country'];
            $address->setAddress($fullAddress);
            $address->setStreet($data['street']);
            $address->setZipCode($data['zipCode']);
            $address->setCity($data['city']);
            $address->setCountry($data['country']);
            $address->setGeolocationStatus(GeolocationStatus::Geolocated);
            $professional->setAddress($address);

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data['password']
            );
            $user->setPassword($hashedPassword);
            $user->setStatus(UserStatus::Active);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'message' => 'api.messages.professional_pending_validation',

            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/auth/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json([
            'message' => 'Login endpoint - géré par le firewall'
        ]);
    }

    #[Route('/auth/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
      $response = new JsonResponse(['message' => 'api.messages.logout_successfully']);
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

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $userType = $this->getCurrentUserType();
        $current = $this->getUser();

        if ($userType === UserType::Admin && $current instanceof Administrator) {
            return $this->json([
                'type' => UserType::Admin->value,
                'email' => $current->getUserIdentifier(),
                'roles' => $current->getRoles(),
            ]);
        }

        if (!$current instanceof User) {
            return $this->json(['error' => 'Unexpected authenticated user type'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = [
            'firstName' => $current->getFirstName(),
            'lastName' => $current->getLastName(),
            'email' => $current->getUserIdentifier(),
            'roles' => $current->getRoles(),
        ];

        $professional = $current->getProfessional();
        if ($professional !== null) {
            $data['siren'] = $professional->getSiren();
            $data['companyName'] = $professional->getCompanyName();
        }

        return $this->json($data);
    }
}
