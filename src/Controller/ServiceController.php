<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services', name: 'api_services_')]
class ServiceController extends AbstractController
{
    public function __construct(private readonly ServiceRepository $serviceRepository) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $services = $this->serviceRepository->findBy([], ['name' => 'ASC']);

            return $this->json([
                'services' => array_values(array_map(static fn($service): string => (string) $service->getName(), $services)),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
