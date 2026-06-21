<?php

namespace App\Controller;

<<<<<<< Updated upstream
use App\Entity\Enum\ProfessionalInteractionHistoryAction;
use App\Entity\Enum\ProfessionalStatus;
use App\Entity\ProfessionalInteractionHistory;
use App\Repository\UserRepository;
use App\Repository\ProfessionalRepository;
=======
use App\Entity\Enum\LaundromatInteractionHistoryAction;
use App\Entity\Enum\LaundromatStatus;
use App\Entity\Enum\ProfessionalInteractionHistoryAction;
use App\Entity\Enum\ProfessionalStatus;
use App\Entity\Laundromat;
use App\Entity\LaundromatInteractionHistory;
use App\Entity\Professional;
use App\Entity\ProfessionalInteractionHistory;
use App\Repository\LaundromatRatingReportRepository;
use App\Repository\LaundromatRepository;
use App\Repository\ProfessionalRepository;
use App\Repository\UserRepository;
use App\Service\LaundromatHydrator;
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
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
=======
        private readonly LaundromatRepository $laundromatRepository,
        private readonly LaundromatRatingReportRepository $ratingReportRepository,
        private readonly ValidatorInterface $validator,
        private readonly LaundromatHydrator $laundromatHydrator,
    ) {}

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        try {
            $pendingPros = $this->userRepository->countPendingProfessionals();
            $pendingLaundries = $this->laundromatRepository->countPendingLaundromats();
            $reports = $this->ratingReportRepository->countOpenReports();

            return $this->json([
                'pendingPros' => $pendingPros,
                'pendingLaundries' => $pendingLaundries,
                'reports' => $reports,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/pros/pending', name: 'pros_pending', methods: ['GET'])]
    public function listPendingProfessionals(): JsonResponse
    {
        try {
            $professionalPendings = $this->userRepository->findPendingProfessionals();

            return $this->json($professionalPendings, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
>>>>>>> Stashed changes
        }
    }

    #[Route('/pros/{id}/status', name: 'pros_update_status', methods: ['PATCH'])]
<<<<<<< Updated upstream
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

=======
    public function updateProfessionalStatus(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $status = $data['status'] ?? null;

            if (!in_array($status, ['validated', 'refused'], true)) {
                return $this->json(['error' => 'api.messages.invalid_status'], Response::HTTP_BAD_REQUEST);
            }

            if ($status === 'refused' && (empty($data['reason']) || trim($data['reason']) === '')) {
                return $this->json(['error' => 'api.messages.reason_required_for_refusal'], Response::HTTP_BAD_REQUEST);
            }

            $professional = $this->professionalRepository->find($id);
            if (!$professional instanceof Professional) {
                return $this->json(['error' => 'api.messages.professional_not_found'], Response::HTTP_NOT_FOUND);
            }

            if ($professional->getStatus() !== ProfessionalStatus::Pending) {
                return $this->json(['error' => 'api.messages.professional_not_pending'], Response::HTTP_BAD_REQUEST);
            }

>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
            $history->setAdministrator($admin);
=======
            $history->setAdministrator($this->getUser());
>>>>>>> Stashed changes
            $history->setProfessional($professional);
            $history->setAction($action);
            $history->setActionReason($reason);
            $history->setDate(new \DateTimeImmutable());

            $errors = $this->validator->validate($history);
            if (count($errors) > 0) {
<<<<<<< Updated upstream
                $errorsString = (string) $errors;
                return $this->json([
                    'error' => $errorsString
                ], Response::HTTP_BAD_REQUEST);
=======
                return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
>>>>>>> Stashed changes
            }

            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return $this->json([
                'message' => $status === 'validated'
                    ? 'api.messages.professional_validated'
                    : 'api.messages.professional_refused',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
<<<<<<< Updated upstream
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
=======
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/laundries/pending', name: 'laundries_pending', methods: ['GET'])]
    public function listPendingLaundries(): JsonResponse
    {
        try {
            $laundromats = $this->laundromatRepository->findPendingLaundromats();

            return $this->json($laundromats, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/laundries/{id}/status', name: 'laundries_update_status', methods: ['PATCH'])]
    public function updateLaundromatStatus(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $status = $data['status'] ?? null;
            $reason = $data['reason'] ?? null;

            if (!in_array($status, ['validated', 'refused'], true)) {
                return $this->json(['error' => 'api.messages.invalid_status'], Response::HTTP_BAD_REQUEST);
            }

            if ($status === 'refused' && (empty($reason) || trim((string) $reason) === '')) {
                return $this->json(['error' => 'api.messages.reason_required_for_refusal'], Response::HTTP_BAD_REQUEST);
            }

            $laundromat = $this->laundromatRepository->find($id);
            if (!$laundromat instanceof Laundromat || $laundromat->getDeletedAt() !== null) {
                return $this->json(['error' => 'api.messages.laundry_not_found'], Response::HTTP_NOT_FOUND);
            }

            $isInitialReview = $laundromat->getStatus() === LaundromatStatus::Pending;
            $hasPendingChanges = $laundromat->hasPendingChanges();

            if (!$isInitialReview && !$hasPendingChanges) {
                return $this->json(['error' => 'api.messages.laundry_already_validated_or_refused'], Response::HTTP_BAD_REQUEST);
            }

            if ($status === 'validated') {
                if ($hasPendingChanges) {
                    $payload = $laundromat->getPendingChanges() ?? [];
                    $hydrateError = $this->laundromatHydrator->hydrate($laundromat, $payload);
                    if ($hydrateError instanceof JsonResponse) {
                        return $hydrateError;
                    }
                    $laundromat->setPendingChanges(null);
                } else {
                    $laundromat->setStatus(LaundromatStatus::Validated);
                }

                $action = LaundromatInteractionHistoryAction::Updated;
                $reasonText = $reason ?? 'Validated by admin';
                $message = $hasPendingChanges
                    ? 'api.messages.laundry_changes_validated'
                    : 'api.messages.laundry_validated';
            } else {
                if ($hasPendingChanges) {
                    $laundromat->setPendingChanges(null);
                    $message = 'api.messages.laundry_changes_refused';
                } else {
                    $laundromat->setStatus(LaundromatStatus::Refused);
                    $message = 'api.messages.laundry_refused';
                }

                $action = LaundromatInteractionHistoryAction::Updated;
                $reasonText = $reason;
            }

            $history = new LaundromatInteractionHistory();
            $history->setAdministrator($this->getUser());
            $history->setLaundromat($laundromat);
            $history->setAction($action);
            $history->setActionReason((string) $reasonText);
            $history->setDate(new \DateTimeImmutable());

            $errors = $this->validator->validate($history);
            if (count($errors) > 0) {
                return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return $this->json(['message' => $message], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
>>>>>>> Stashed changes
        }
    }
}
