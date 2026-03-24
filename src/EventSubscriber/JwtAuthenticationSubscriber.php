<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

class JwtAuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%lexik_jwt_authentication.token_ttl%')]
        private int $tokenTtl,
        private readonly EntityManagerInterface $entityManager,
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

        if ($user instanceof User) {
            $user->setLastConnectionDate(new \DateTimeImmutable());
            $this->entityManager->flush();
        }

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