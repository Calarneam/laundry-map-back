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
<<<<<<< Updated upstream
=======
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
=======

            try {
                $email = (new Email())
                    ->from('contact@sashacarton.fr')
                    ->to($user->getEmail())
                    ->subject('Bienvenue sur Laundry Map !')
                    ->html($this->emailLayout('Bienvenue sur Laundry Map', '
                        <h2 style="margin:0 0 16px;color:#111827;font-size:22px;font-weight:700;">Bienvenue ' . htmlspecialchars($user->getFirstName()) . ' ! 👋</h2>
                        <p style="margin:0 0 12px;color:#374151;font-size:15px;line-height:1.6;">Votre compte a été créé avec succès sur <strong>Laundry Map</strong>.</p>
                        <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6;">Vous pouvez dès maintenant vous connecter et découvrir les laveries autour de vous.</p>
                        <div style="text-align:center;margin:32px 0;">
                          <a href="http://localhost:5173/login" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:15px;font-weight:600;">Se connecter</a>
                        </div>
                        <p style="margin:0;color:#6b7280;font-size:14px;">À bientôt,<br><strong>L\'équipe Laundry Map</strong></p>
                    '));
                $this->mailer->send($email);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send welcome email: ' . $e->getMessage());
            }
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
=======

            try {
                $email = (new Email())
                    ->from('contact@sashacarton.fr')
                    ->to($user->getEmail())
                    ->subject('Bienvenue sur Laundry Map - Compte Professionnel')
                    ->html($this->emailLayout('Compte Professionnel Laundry Map', '
                        <h2 style="margin:0 0 16px;color:#111827;font-size:22px;font-weight:700;">Bienvenue ' . htmlspecialchars($user->getFirstName()) . ' ! 🏪</h2>
                        <p style="margin:0 0 12px;color:#374151;font-size:15px;line-height:1.6;">Votre compte professionnel a été créé avec succès sur <strong>Laundry Map</strong>.</p>
                        <div style="background-color:#fef9c3;border-left:4px solid #eab308;border-radius:6px;padding:16px;margin:24px 0;">
                          <p style="margin:0;color:#854d0e;font-size:14px;font-weight:600;">⏳ Validation en cours</p>
                          <p style="margin:8px 0 0;color:#854d0e;font-size:14px;">Votre demande est en cours d\'examen par notre équipe. Vous recevrez un email dès que votre compte sera activé.</p>
                        </div>
                        <p style="margin:0;color:#6b7280;font-size:14px;">À bientôt,<br><strong>L\'équipe Laundry Map</strong></p>
                    '));
                $this->mailer->send($email);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send welcome email: ' . $e->getMessage());
            }
>>>>>>> Stashed changes

            return $this->json([
                'message' => 'api.messages.professional_pending_validation',

            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

<<<<<<< Updated upstream
=======
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
                ->html($this->emailLayout('Réinitialisation du mot de passe', '
                    <h2 style="margin:0 0 16px;color:#111827;font-size:22px;font-weight:700;">Réinitialisation du mot de passe 🔐</h2>
                    <p style="margin:0 0 12px;color:#374151;font-size:15px;line-height:1.6;">Bonjour <strong>' . htmlspecialchars($user->getFirstName()) . '</strong>,</p>
                    <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6;">Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en choisir un nouveau.</p>
                    <div style="text-align:center;margin:32px 0;">
                      <a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:15px;font-weight:600;">Réinitialiser mon mot de passe</a>
                    </div>
                    <div style="background-color:#fef2f2;border-left:4px solid #ef4444;border-radius:6px;padding:16px;margin:24px 0;">
                      <p style="margin:0;color:#991b1b;font-size:14px;">⚠️ Ce lien expire dans <strong>1 heure</strong>. Si vous n\'avez pas fait cette demande, ignorez cet email.</p>
                    </div>
                    <p style="margin:0;color:#6b7280;font-size:14px;"><strong>L\'équipe Laundry Map</strong></p>
                '));
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
                ->html($this->emailLayout('Compte Professionnel Laundry Map', '
                    <h2 style="margin:0 0 16px;color:#111827;font-size:22px;font-weight:700;">Profil professionnel complété ! 🏪</h2>
                    <p style="margin:0 0 12px;color:#374151;font-size:15px;line-height:1.6;">Bonjour <strong>' . htmlspecialchars($currentUser->getFirstName()) . '</strong>,</p>
                    <p style="margin:0 0 12px;color:#374151;font-size:15px;line-height:1.6;">Votre profil professionnel a été complété avec succès sur <strong>Laundry Map</strong>.</p>
                    <div style="background-color:#fef9c3;border-left:4px solid #eab308;border-radius:6px;padding:16px;margin:24px 0;">
                      <p style="margin:0;color:#854d0e;font-size:14px;font-weight:600;">⏳ Validation en cours</p>
                      <p style="margin:8px 0 0;color:#854d0e;font-size:14px;">Votre demande est en cours d\'examen par notre équipe. Vous recevrez un email dès que votre compte sera activé.</p>
                    </div>
                    <p style="margin:0;color:#6b7280;font-size:14px;">À bientôt,<br><strong>L\'équipe Laundry Map</strong></p>
                '));
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send pro welcome email: ' . $e->getMessage());
        }

        return $this->json([
            'message' => 'api.messages.professional_pending_validation',
        ], Response::HTTP_CREATED);
    }

    private function emailLayout(string $title, string $content): string
    {
        return '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . $title . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          <!-- Header -->
          <tr>
            <td style="background-color:#2563eb;border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:-0.5px;">🧺 Laundry Map</h1>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="background-color:#ffffff;padding:40px;border-radius:0 0 12px 12px;">
              ' . $content . '
              <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">
              <p style="margin:0;color:#9ca3af;font-size:13px;text-align:center;">
                © ' . date('Y') . ' Laundry Map · Cet email a été envoyé automatiquement, merci de ne pas y répondre.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
    }

>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
=======
            'hasPassword' => $current->getPassword() !== null,
            'hasOauth' => $current->getOauthId() !== null,
>>>>>>> Stashed changes
        ];

        $professional = $current->getProfessional();
        if ($professional !== null) {
            $data['siren'] = $professional->getSiren();
            $data['companyName'] = $professional->getCompanyName();
        }

        return $this->json($data);
    }
}
