<?php

namespace App\Controller;

use App\Entity\LaundromatRating;
use App\Entity\LaundromatRatingReport;
use App\Entity\User;
use App\Entity\Enum\Report;
use App\Repository\LaundromatRepository;
use App\Repository\LaundromatRatingRepository;
use App\Repository\LaundromatRatingReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_rating_')]
class RatingController extends AbstractApiController
{
    public function __construct(
        private readonly LaundromatRepository $laundromatRepository,
        private readonly LaundromatRatingRepository $ratingRepository,
        private readonly LaundromatRatingReportRepository $reportRepository,
    ) {}

    // GET /api/laundromat/{id}/ratings?limit=10&offset=0
    #[Route('/laundromat/{id}/ratings', name: 'list', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function list(int $id, Request $request): JsonResponse
    {
        $laundromat = $this->laundromatRepository->find($id);
        if (!$laundromat) {
            return $this->json(['error' => 'api.messages.laundromat_not_found'], Response::HTTP_NOT_FOUND);
        }

        $limit  = max(1, min(50, (int) $request->query->get('limit', 10)));
        $offset = max(0, (int) $request->query->get('offset', 0));

        $ratings = $this->ratingRepository->findByLaundromat($laundromat, $limit, $offset);
        $total   = $this->ratingRepository->countByLaundromat($laundromat);

        return $this->json([
            'total'   => $total,
            'limit'   => $limit,
            'offset'  => $offset,
            'ratings' => array_map(fn($r) => $this->serializeRating($r), $ratings),
        ]);
    }

    // POST /api/laundromat/{id}/ratings
    #[Route('/laundromat/{id}/ratings', name: 'create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function create(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $laundromat = $this->laundromatRepository->find($id);
        if (!$laundromat) {
            return $this->json(['error' => 'api.messages.laundromat_not_found'], Response::HTTP_NOT_FOUND);
        }

        // RG0015 / RG0018 — one rating/comment per user per laundromat
        $existing = $this->ratingRepository->findOneByLaundromatAndUser($laundromat, $user);
        if ($existing) {
            return $this->json(['error' => 'api.messages.rating_already_exists'], Response::HTTP_CONFLICT);
        }

        $data    = json_decode($request->getContent(), true) ?? [];
        $rating  = isset($data['rating']) ? (int) $data['rating'] : null;
        $comment = isset($data['comment']) ? trim((string) $data['comment']) : null;

        if ($rating === null || $rating < 1 || $rating > 5) {
            return $this->json(['error' => 'api.messages.invalid_rating'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // RG0019 — 512 chars max (entity allows 500, enforce 512 at controller level)
        if ($comment !== null && mb_strlen($comment) > 512) {
            return $this->json(['error' => 'api.messages.comment_too_long'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entity = new LaundromatRating();
        $entity->setLaundromat($laundromat);
        $entity->setUser($user);
        $entity->setRating($rating);
        $entity->setRatedAt(new \DateTimeImmutable());

        if ($comment !== null && $comment !== '') {
            $entity->setComment($comment);
            $entity->setCommentedAt(new \DateTimeImmutable());
        }

        $em->persist($entity);
        $em->flush();

        return $this->json($this->serializeRating($entity), Response::HTTP_CREATED);
    }

    // PATCH /api/ratings/{id}
    #[Route('/ratings/{id}', name: 'update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $rating = $this->ratingRepository->find($id);
        if (!$rating || $rating->getUser() !== $user) {
            return $this->json(['error' => 'api.messages.rating_not_found'], Response::HTTP_NOT_FOUND);
        }

        $data       = json_decode($request->getContent(), true) ?? [];
        $newRating  = isset($data['rating'])  ? (int) $data['rating']          : null;
        $newComment = isset($data['comment']) ? trim((string) $data['comment']) : null;

        if ($newRating !== null) {
            if ($newRating < 1 || $newRating > 5) {
                return $this->json(['error' => 'api.messages.invalid_rating'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $rating->setRating($newRating);
            $rating->setRatedAt(new \DateTimeImmutable());
        }

        if ($newComment !== null) {
            if (mb_strlen($newComment) > 512) {
                return $this->json(['error' => 'api.messages.comment_too_long'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $rating->setComment($newComment !== '' ? $newComment : null);
            $rating->setCommentedAt($newComment !== '' ? new \DateTimeImmutable() : null);
        }

        $em->flush();

        // RG0016 — average recalculated on next GET (computed dynamically)
        return $this->json($this->serializeRating($rating));
    }

    // DELETE /api/ratings/{id}
    #[Route('/ratings/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $rating = $this->ratingRepository->find($id);
        if (!$rating || $rating->getUser() !== $user) {
            return $this->json(['error' => 'api.messages.rating_not_found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($rating);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // POST /api/ratings/{id}/report
    #[Route('/ratings/{id}/report', name: 'report', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function report(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'api.messages.profile_forbidden'], Response::HTTP_FORBIDDEN);
        }

        $rating = $this->ratingRepository->find($id);
        if (!$rating) {
            return $this->json(['error' => 'api.messages.rating_not_found'], Response::HTTP_NOT_FOUND);
        }

        // Prevent duplicate reports
        $existingReport = $this->reportRepository->findOneBy(['rating' => $rating, 'user' => $user]);
        if ($existingReport) {
            return $this->json(['error' => 'api.messages.report_already_exists'], Response::HTTP_CONFLICT);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $reason = $data['reason'] ?? null;

        // reason is non-nullable on the entity — require it
        if ($reason === null) {
            return $this->json(['error' => 'api.messages.missing_reason'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $reasonEnum = Report::from((string) $reason);
        } catch (\ValueError) {
            return $this->json(['error' => 'api.messages.invalid_reason'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $comment = isset($data['comment']) ? trim((string) $data['comment']) : null;

        $report = new LaundromatRatingReport();
        $report->setRating($rating);
        $report->setUser($user);
        $report->setDate(new \DateTimeImmutable());
        $report->setReason($reasonEnum);
        if ($comment !== null && $comment !== '') {
            $report->setComment($comment);
        }

        $em->persist($report);
        $em->flush();

        return $this->json(['message' => 'api.messages.report_submitted'], Response::HTTP_CREATED);
    }

    private function serializeRating(LaundromatRating $r): array
    {
        $user = $r->getUser();
        return [
            'id'          => $r->getId(),
            'rating'      => $r->getRating(),
            'comment'     => $r->getComment(),
            'ratedAt'     => $r->getRatedAt()?->format('Y-m-d'),
            'commentedAt' => $r->getCommentedAt()?->format('Y-m-d'),
            'response'    => $r->getResponse(),
            'respondedAt' => $r->getRespondedAt()?->format('Y-m-d'),
            'user'        => $user ? [
                'id'        => $user->getId(),
                'firstName' => method_exists($user, 'getFirstName') ? $user->getFirstName() : null,
                'lastName'  => method_exists($user, 'getLastName')  ? $user->getLastName()  : null,
            ] : null,
        ];
    }
}
