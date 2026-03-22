<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Enum\UserStatus;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[Route('/api/auth/google', name: 'api_auth_google_')]
class GoogleAuthController extends AbstractApiController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
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
    public function handleCallback(Request $request, UserRepository $userRepository): Response
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
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setOauthId($googleId);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setStatus(UserStatus::Active);
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($user);
            } else {
                if (!$user->getOauthId()) {
                    $user->setOauthId($googleId);
                }
                $user->setUpdatedAt(new \DateTimeImmutable());
            }

            $user->setLastConnectionDate(new \DateTimeImmutable());
            $this->entityManager->flush();

            // 4. Générer le JWT et le poser en cookie HTTP-only 
            $jwt = $this->jwtManager->create($user);

            $isSecure = $this->appEnv === 'prod';

            $response = new RedirectResponse($frontendUrl);
            $response->headers->setCookie(
                Cookie::create('BEARER')
                    ->withValue($jwt)
                    ->withPath('/')
                    ->withSecure($isSecure)
                    ->withHttpOnly(true)
                    ->withSameSite('lax')
            );

            return $response;

        } catch (\Throwable $e) {
            return new RedirectResponse($frontendUrl . '/login?error=google_auth_failed');
        }
    }
}
