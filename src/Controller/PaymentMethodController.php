<?php

namespace App\Controller;

use App\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/payment-methods', name: 'api_payment_methods_')]
class PaymentMethodController extends AbstractController
{
    public function __construct(private readonly PaymentMethodRepository $paymentMethodRepository) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            return $this->json([
                'paymentMethods' => $this->paymentMethodRepository->findAllNames(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
