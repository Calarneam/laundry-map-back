<?php

namespace App\Service;

use App\Entity\LaundromatRating;
use App\Entity\User;

final class RatingSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serializePublic(LaundromatRating $rating): array
    {
        $user = $rating->getUser();

        return [
            'id' => $rating->getId(),
            'rating' => $rating->getRating(),
            'comment' => $rating->getComment(),
            'ratedAt' => $rating->getRatedAt()?->format('Y-m-d'),
            'commentedAt' => $rating->getCommentedAt()?->format('Y-m-d'),
            'response' => $rating->getResponse(),
            'respondedAt' => $rating->getRespondedAt()?->format('Y-m-d'),
            'user' => $user instanceof User ? [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ] : null,
        ];
    }
}
