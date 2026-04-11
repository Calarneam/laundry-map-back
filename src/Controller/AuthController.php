<?php

namespace App\Controller;

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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

use function count;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractApiController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ProfessionalRepository $professionalRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email'])) {
                return $this->json([
                    'error' => 'api.messages.missing_fields'
                ], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
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

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            try {
                $email = (new Email())
                    ->from('contact@sashacarton.fr')
                    ->to($user->getEmail())
                    ->subject('Bienvenue sur Laundry Map !')
                    ->html(
                        '<h1>Bienvenue ' . htmlspecialchars($user->getFirstName()) . ' !</h1>' .
                        '<p>Votre compte a été créé avec succès sur Laundry Map.</p>' .
                        '<p>Vous pouvez dès maintenant vous connecter et découvrir les laveries autour de vous.</p>' .
                        '<p>À bientôt,<br>L\'équipe Laundry Map</p>'
                    );
                $this->mailer->send($email);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send welcome email: ' . $e->getMessage());
            }

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

    #[Route('/register/professional', name: 'register_professional', methods: ['POST'])]
    public function registerProfessional(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        HttpClientInterface $httpClient
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['siren'])) {
                return $this->json([
                    'error' => 'api.messages.missing_fields'
                ], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return $this->json([
                    'error' => 'api.messages.email_already_used'
                ], Response::HTTP_CONFLICT);
            }

            $existingProfessional = $this->professionalRepository->findOneBy(['siren' => $data['siren']]);
            if (!$this->SirenIsExisting($data['siren'], $httpClient) || $existingProfessional !== null) {
                return $this->json([
                    'error' => 'api.messages.siren_not_found_or_already_used'
                ], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $professional = new Professional();

            $user->setProfessional($professional);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setEmail($data['email']);

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

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $errors = $this->validator->validate($professional);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $errors = $this->validator->validate($address);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            try {
                $email = (new Email())
                    ->from('contact@sashacarton.fr')
                    ->to($user->getEmail())
                    ->subject('Bienvenue sur Laundry Map - Compte Professionnel')
                    ->html(
                        '<h1>Bienvenue ' . htmlspecialchars($user->getFirstName()) . ' !</h1>' .
                        '<p>Votre compte professionnel a été créé avec succès sur Laundry Map.</p>' .
                        '<p>Votre demande est en attente de validation. Vous recevrez un email dès que votre compte sera activé.</p>' .
                        '<p>À bientôt,<br>L\'équipe Laundry Map</p>'
                    );
                $this->mailer->send($email);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send welcome email: ' . $e->getMessage());
            }

            return $this->json([
                'message' => 'api.messages.professional_pending_validation',

            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        // Always return success to prevent email enumeration
        if (!$user) {
            return $this->json(['message' => 'api.messages.reset_email_sent']);
        }

        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->entityManager->flush();

        $frontendUrl = $this->getParameter('app.frontend_url');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token;

        try {
            $email = (new Email())
                ->from('contact@sashacarton.fr')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe - Laundry Map')
                ->html(
                    '<h1>Réinitialisation du mot de passe</h1>' .
                    '<p>Bonjour ' . htmlspecialchars($user->getFirstName()) . ',</p>' .
                    '<p>Vous avez demandé la réinitialisation de votre mot de passe.</p>' .
                    '<p><a href="' . htmlspecialchars($resetUrl) . '">Cliquez ici pour réinitialiser votre mot de passe</a></p>' .
                    '<p>Ce lien expire dans 1 heure.</p>' .
                    '<p>Si vous n\'avez pas fait cette demande, ignorez cet email.</p>' .
                    '<p>L\'équipe Laundry Map</p>'
                );
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send reset email: ' . $e->getMessage());
        }

        return $this->json(['message' => 'api.messages.reset_email_sent']);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token']) || !isset($data['password'])) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['resetToken' => $data['token']]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'api.messages.invalid_or_expired_token'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json(['message' => 'api.messages.password_reset_success']);
    }

    #[Route('/complete-professional-profile', name: 'complete_professional_profile', methods: ['POST'])]
    public function completeProfessionalProfile(
        Request $request,
        HttpClientInterface $httpClient,
    ): JsonResponse {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($currentUser->getProfessional() !== null) {
            return $this->json(['error' => 'api.messages.already_professional'], Response::HTTP_CONFLICT);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['siren']) || !isset($data['companyName'])) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $existingProfessional = $this->professionalRepository->findOneBy(['siren' => $data['siren']]);
        if (!$this->SirenIsExisting($data['siren'], $httpClient) || $existingProfessional !== null) {
            return $this->json([
                'error' => 'api.messages.siren_not_found_or_already_used'
            ], Response::HTTP_CONFLICT);
        }

        $professional = new Professional();
        $professional->setSiren($data['siren']);
        $professional->setCompanyName($data['companyName']);
        $professional->setCodeApe($data['codeApe'] ?? null);
        $professional->setStatus(ProfessionalStatus::Pending);

        $address = new Address();
        $fullAddress = ($data['street'] ?? '') . ', ' . ($data['zipCode'] ?? '') . ' ' . ($data['city'] ?? '') . ', ' . ($data['country'] ?? '');
        $address->setAddress($fullAddress);
        $address->setStreet($data['street'] ?? '');
        $address->setZipCode($data['zipCode'] ?? '');
        $address->setCity($data['city'] ?? '');
        $address->setCountry($data['country'] ?? '');
        $address->setGeolocationStatus(GeolocationStatus::Geolocated);
        $professional->setAddress($address);

        $currentUser->setProfessional($professional);
        $currentUser->setUpdatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($professional);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($address);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        try {
            $email = (new Email())
                ->from('contact@sashacarton.fr')
                ->to($currentUser->getEmail())
                ->subject('Bienvenue sur Laundry Map - Compte Professionnel')
                ->html(
                    '<h1>Bienvenue ' . htmlspecialchars($currentUser->getFirstName()) . ' !</h1>' .
                    '<p>Votre profil professionnel a été complété avec succès sur Laundry Map.</p>' .
                    '<p>Votre demande est en attente de validation. Vous recevrez un email dès que votre compte sera activé.</p>' .
                    '<p>À bientôt,<br>L\'équipe Laundry Map</p>'
                );
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send pro welcome email: ' . $e->getMessage());
        }

        return $this->json([
            'message' => 'api.messages.professional_pending_validation',
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json([
            'message' => 'Login endpoint - géré par le firewall'
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
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
        $current = $this->getUser();

        if ($current->getRoles() === ['ROLE_ADMIN']) {
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
            'hasPassword' => $current->getPassword() !== null,
            'hasOauth' => $current->getOauthId() !== null,
        ];

        $professional = $current->getProfessional();
        if ($professional !== null) {
            $data['siren'] = $professional->getSiren();
            $data['companyName'] = $professional->getCompanyName();
        }

        return $this->json($data);
    }
}
