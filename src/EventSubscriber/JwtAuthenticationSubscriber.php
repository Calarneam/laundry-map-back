<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\Enum\UserStatus;
use App\Entity\Enum\ProfessionalStatus;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class JwtAuthenticationSubscriber implements EventSubscriberInterface
{

    public function __construct(
        #[Autowire('%lexik_jwt_authentication.token_ttl%')]
        private int $tokenTtl
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $response = $event->getResponse();

        $expiration = (new \DateTime())->add(new \DateInterval('PT' . $this->tokenTtl . 'S'));
        $cookie = Cookie::create('USER_ROLE')
            ->withValue(implode(',', $user->getRoles()))
            ->withExpires($expiration)
            ->withPath('/')
            ->withSecure(true)
            ->withSameSite('lax')
            ->withHttpOnly(false);

        $response->headers->setCookie($cookie);
    }
}