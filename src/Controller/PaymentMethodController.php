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
            $paymentMethods = $this->paymentMethodRepository->findBy([], ['name' => 'ASC']);

            return $this->json([
                'paymentMethods' => array_values(array_map(static fn($method): string => (string) $method->getName(), $paymentMethods)),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
