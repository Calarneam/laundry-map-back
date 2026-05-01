<?php

namespace App\Controller;

use App\Controller\AbstractApiController;
use App\Entity\Laundromat;
use App\Entity\User;
use App\Repository\LaundromatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user', name: 'api_user_')]
class UserController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LaundromatRepository $laundromatRepository,
    ) {}

    #[Route('/favorites', name: 'get_favorites', methods: ['GET'])]
    public function getFavorites(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if (!$latitude || !$longitude) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            $this->laundromatRepository->findFavorites($user, (float) $latitude, (float) $longitude),
            Response::HTTP_OK,
        );
    }

    #[Route('/favorites', name: 'add_favorite', methods: ['POST'])]
    public function addFavorite(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['laundromatId'])) {
            return $this->json(['error' => 'api.messages.missing_fields'], Response::HTTP_BAD_REQUEST);
        }

        $laundromat = $this->entityManager->getRepository(Laundromat::class)->find($data['laundromatId']);
        if (!$laundromat) {
            return $this->json(['error' => 'api.messages.laundromat_not_found'], Response::HTTP_NOT_FOUND);
        }

        $user->getFavoriteLaundromats()->add($laundromat);
        $this->entityManager->flush();

        return $this->json(['message' => 'api.messages.favorite_added'], Response::HTTP_OK);
    }
}