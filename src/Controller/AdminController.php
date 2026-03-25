<?php

namespace App\Controller;

use App\Entity\Enum\ProfessionalInteractionHistoryAction;
use App\Entity\Enum\ProfessionalStatus;
use App\Entity\ProfessionalInteractionHistory;
use App\Repository\UserRepository;
use App\Repository\ProfessionalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin', name: 'api_admin_')]
class AdminController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly ProfessionalRepository $professionalRepository,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/pros/pending', name: 'pros_pending', methods: ['GET'])]
    public function listPendingProfessionals(): JsonResponse {
        try {
            $professionalPendings = $this->userRepository->findPendingProfessionals();
            
            return $this->json($professionalPendings, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/pros/{id}/status', name: 'pros_update_status', methods: ['PATCH'])]
    public function updateProfessionalStatus(
        int $id,
        Request $request,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['status'])) {
                return $this->json([
                    'error' => 'api.messages.missing_fields'
                ], Response::HTTP_BAD_REQUEST);
            }

            $status = $data['status'];

            if (!in_array($status, ['validated', 'refused'], true)) {
                return $this->json([
                    'error' => 'api.messages.invalid_status'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($status === 'refused' && (empty($data['reason']) || trim($data['reason']) === '')) {
                return $this->json([
                    'error' => 'api.messages.reason_required_for_refusal'
                ], Response::HTTP_BAD_REQUEST);
            }

            $professional = $this->professionalRepository->find($id);

            if (!$professional) {
                return $this->json([
                    'error' => 'api.messages.professional_not_found'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($professional->getStatus() !== ProfessionalStatus::Pending) {
                return $this->json([
                    'error' => 'api.messages.professional_not_pending'
                ], Response::HTTP_BAD_REQUEST);
            }

            $admin = $this->getUser();

            if ($status === 'validated') {
                $professional->setStatus(ProfessionalStatus::Validated);
                $professional->setValidationDate(new \DateTimeImmutable());
                $reason = $data['reason'] ?? 'Validated by admin';
                $action = ProfessionalInteractionHistoryAction::Validated;
            } else {
                $professional->setStatus(ProfessionalStatus::Refused);
                $reason = $data['reason'];
                $action = ProfessionalInteractionHistoryAction::Refused;
            }

            $history = new ProfessionalInteractionHistory();
            $history->setAdministrator($admin);
            $history->setProfessional($professional);
            $history->setAction($action);
            $history->setActionReason($reason);
            $history->setDate(new \DateTimeImmutable());

            $errors = $this->validator->validate($history);
            if (count($errors) > 0) {
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return $this->json([
                'message' => $status === 'validated'
                    ? 'api.messages.professional_validated'
                    : 'api.messages.professional_refused',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
