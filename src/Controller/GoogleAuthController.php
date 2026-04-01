<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Enum\UserStatus;
use App\Repository\UserRepository;
use App\Security\UserChecker;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/auth/google', name: 'api_auth_google_')]
class GoogleAuthController extends AbstractApiController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthenticationSuccessHandler $authenticationSuccessHandler,
        private readonly UserChecker $userChecker,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('', name: 'redirect', methods: ['GET'])]
    public function redirectToGoogle(): RedirectResponse
    {
        $params = http_build_query([
            'client_id' => $this->getParameter('app.google_client_id'),
            'redirect_uri' => $this->getParameter('app.google_redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return new RedirectResponse('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    #[Route('/callback', name: 'callback', methods: ['GET'])]
    public function handleCallback(Request $request): Response
    {
        $code = $request->query->get('code');
        $frontendUrl = $this->getParameter('app.frontend_url');

        if (!$code) {
            return new RedirectResponse($frontendUrl . '/login?error=google_auth_failed');
        }

        try {
            // 1. Échanger le code contre un access_token
            $tokenResponse = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code' => $code,
                    'client_id' => $this->getParameter('app.google_client_id'),
                    'client_secret' => $this->getParameter('app.google_client_secret'),
                    'redirect_uri' => $this->getParameter('app.google_redirect_uri'),
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $tokenData = $tokenResponse->toArray();
            $accessToken = $tokenData['access_token'];

            // 2. Récupérer le profil utilisateur Google
            $userInfoResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $userInfo = $userInfoResponse->toArray();
            $email = $userInfo['email'];
            $googleId = $userInfo['sub'];
            $firstName = $userInfo['given_name'] ?? null;
            $lastName = $userInfo['family_name'] ?? null;

            // 3. Trouver ou créer l'utilisateur
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setOauthId($googleId);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setStatus(UserStatus::Active);
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());

                $errors = $this->validator->validate($user);
                if (count($errors) > 0) {
                    return new RedirectResponse($frontendUrl . '/login?error=google_auth_failed');
                }

                $this->entityManager->persist($user);

                try {
                    $welcomeEmail = (new Email())
                        ->from('contact@sashacarton.fr')
                        ->to($user->getEmail())
                        ->subject('Bienvenue sur Laundry Map !')
                        ->html(
                            '<h1>Bienvenue ' . htmlspecialchars($user->getFirstName()) . ' !</h1>' .
                            '<p>Votre compte a été créé avec succès sur Laundry Map via Google.</p>' .
                            '<p>Vous pouvez dès maintenant découvrir les laveries autour de vous.</p>' .
                            '<p>À bientôt,<br>L\'équipe Laundry Map</p>'
                        );
                    $this->mailer->send($welcomeEmail);
                } catch (\Exception $e) {
                    $this->logger->error('Failed to send welcome email: ' . $e->getMessage());
                }
            } else {
                if (!$user->getOauthId()) {
                    $user->setOauthId($googleId);
                }
                $user->setUpdatedAt(new \DateTimeImmutable());
            }

            $this->entityManager->flush();

            try {
                $this->userChecker->checkPreAuth($user);
            } catch (AccountStatusException) {
                return new RedirectResponse($frontendUrl . '/login?error=account_restricted');
            }

            // 4. Générer le JWT via Lexik et récupérer le cookie (lastConnectionDate via JwtAuthenticationSubscriber)
            $authResponse = $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);

            $response = new RedirectResponse($frontendUrl);
            foreach ($authResponse->headers->getCookies() as $cookie) {
                $response->headers->setCookie($cookie);
            }

            return $response;

        } catch (\Throwable $e) {
            return new RedirectResponse($frontendUrl . '/login?error=google_auth_failed');
        }
    }
}
