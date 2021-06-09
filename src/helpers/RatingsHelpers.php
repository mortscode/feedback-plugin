<?php

namespace mortscode\feedback\helpers;

// get the new average
use Craft;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;

class RatingsHelpers
{
    /**
     * Returns the average rating for a given entry ID
     *
     * @param int $entryId
     * @return float
     */
    public static function getAverageRating(int $entryId): float {
        $average = FeedbackElement::find()
            ->where([
                'entryId' => $entryId,
                'feedbackStatus' => FeedbackStatus::Approved,
                'feedbackType' => FeedbackType::Review,
            ])
            ->andWhere(['not', ['rating' => null]])
            ->average('rating');

        return round($average, 1);
    }

    public static function getTotalRatings(int $entryId): int {
        return FeedbackElement::find()
            ->where([
                'entryId' => $entryId,
                'feedbackStatus' => FeedbackStatus::Approved,
                'feedbackType' => FeedbackType::Review,
            ])
            ->andWhere(['not', ['rating' => null]])
            ->count();
    }

    public static function getTotalPending(int $entryId): int {
        return FeedbackElement::find()
            ->where([
                'entryId' => $entryId,
                'feedbackStatus' => FeedbackStatus::Pending,
            ])
            ->count();
    }
}