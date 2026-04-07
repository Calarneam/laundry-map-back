<?php

namespace App\Controller;

use App\Service\WiLineApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WilineController extends AbstractApiController {

    public function __construct(
        private readonly WiLineApiService $wiLineApiService,
    ) {}

    #[Route('/api/wi-line/machines/{serial}', name: 'wi_line_machines', methods: ['GET'])]
    public function getMachines(string $serial): JsonResponse
    {
        $machines = $this->wiLineApiService->getLaundryMachines($serial);
        return $this->json($machines, Response::HTTP_OK);
    }

}