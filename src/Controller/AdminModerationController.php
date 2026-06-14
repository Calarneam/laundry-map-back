<?php

namespace App\Controller;

use App\Entity\Administrator;
use App\Entity\Enum\UserInteractionAction;
use App\Entity\Enum\UserStatus;
use App\Entity\LaundromatRating;
use App\Entity\User;
use App\Entity\UserInteractionHistory;
use App\Repository\LaundromatRatingReportRepository;
use App\Repository\LaundromatRatingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin', name: 'api_admin_moderation_')]
class AdminModerationController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LaundromatRatingReportRepository $reportRepository,
        private readonly LaundromatRatingRepository $ratingRepository,
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/reports', name: 'reports_list', methods: ['GET'])]
    public function listReports(): JsonResponse
    {
        try {
            $this->getAdministrator();

            return $this->json($this->reportRepository->findGroupedOpenReports(), Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/reports/{ratingId}/dismiss', name: 'reports_dismiss', methods: ['POST'])]
    public function dismissReports(int $ratingId): JsonResponse
    {
        try {
            $this->getAdministrator();

            $rating = $this->ratingRepository->find($ratingId);
            if (!$rating instanceof LaundromatRating) {
                return $this->json(['error' => 'api.messages.rating_not_found'], Response::HTTP_NOT_FOUND);
            }

            $this->reportRepository->deleteByRating($rating);

            return $this->json(['message' => 'api.messages.report_dismissed'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/ratings/{ratingId}', name: 'ratings_delete', methods: ['DELETE'])]
    public function deleteRatingComment(int $ratingId, Request $request): JsonResponse
    {
        try {
            $this->getAdministrator();

            $rating = $this->ratingRepository->find($ratingId);
            if (!$rating instanceof LaundromatRating) {
                return $this->json(['error' => 'api.messages.rating_not_found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $reason = trim((string) ($data['reason'] ?? ''));
            if ($reason === '') {
                return $this->json(['error' => 'api.messages.reason_required_for_refusal'], Response::HTTP_BAD_REQUEST);
            }

            $rating->setCommentDeletedReason($reason);
            $rating->setCommentDeletedAt(new \DateTimeImmutable());
            $rating->setComment('');
            $rating->setCommentedAt(null);
            $rating->setResponse(null);
            $rating->setRespondedAt(null);

            $this->reportRepository->deleteByRating($rating);
            $this->entityManager->flush();

            return $this->json(['message' => 'api.messages.rating_deleted'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users/{userId}/ban', name: 'users_ban', methods: ['POST'])]
    public function banUser(int $userId, Request $request): JsonResponse
    {
        try {
            $administrator = $this->getAdministrator();

            $user = $this->userRepository->find($userId);
            if (!$user instanceof User) {
                return $this->json(['error' => 'api.messages.profile_not_found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $reason = trim((string) ($data['reason'] ?? ''));
            if ($reason === '') {
                return $this->json(['error' => 'api.messages.reason_required_for_refusal'], Response::HTTP_BAD_REQUEST);
            }

            $user->setStatus(UserStatus::Banned);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $history = new UserInteractionHistory();
            $history->setAdministrator($administrator);
            $history->setUser($user);
            $history->setAction(UserInteractionAction::Banned);
            $history->setActionReason($reason);
            $history->setDate(new \DateTimeImmutable());

            $errors = $this->validator->validate($history);
            if (count($errors) > 0) {
                return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($history);
            $this->entityManager->flush();

            return $this->json(['message' => 'api.messages.user_banned'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
