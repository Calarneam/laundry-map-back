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
        $search = $request->query->get('search');
        $searchValue = \is_string($search) && trim($search) !== '' ? trim($search) : null;

        $hasCoordinates = $latitude !== null && $latitude !== ''
            && $longitude !== null && $longitude !== '';

        if (!$hasCoordinates) {
            return $this->json(
                $this->laundromatRepository->findFavoritesWithoutCoordinates($user, $searchValue),
                Response::HTTP_OK,
            );
        }

        return $this->json(
            $this->laundromatRepository->findFavorites($user, (float) $latitude, (float) $longitude, $searchValue),
            Response::HTTP_OK,
        );
    }

    #[Route('/favorites/{laundromatId}', name: 'remove_favorite', methods: ['DELETE'])]
    public function removeFavorite(int $laundromatId, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $laundromat = $this->laundromatRepository->find($laundromatId);
        if (!$laundromat) {
            return $this->json(['error' => 'api.messages.laundromat_not_found'], Response::HTTP_NOT_FOUND);
        }

        $user->getFavoriteLaundromats()->removeElement($laundromat);
        $entityManager->flush();

        return $this->json(['message' => 'api.messages.favorite_removed'], Response::HTTP_OK);
    }
}