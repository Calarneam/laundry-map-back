<?php

namespace App\Controller;

use App\Repository\SocialLinkTypeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/laundromat/socialmedial', name: 'api_laundromat_socialmedial_')]
class SocialLinkTypeController extends AbstractApiController
{
    public function __construct(
        private readonly SocialLinkTypeRepository $socialLinkTypeRepository,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $types = $this->socialLinkTypeRepository->findBy([], ['id' => 'ASC']);

        return $this->json(array_map(fn($type) => [
            'id' => $type->getId(),
            'code' => $type->getCode(),
            'label' => $type->getLabel(),
            'validationRegex' => $type->getValidationRegex(),
        ], $types), Response::HTTP_OK);
    }
}
