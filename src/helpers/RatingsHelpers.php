<?php

namespace mortscode\feedback\helpers;

// get the new average
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\records\FeedbackRecord;
use mortscode\feedback\records\ReviewsRecord;

class RatingsHelpers
{
    /**
     * Returns the average rating for a given entry ID
     *
     * @param int $entryId
     * @return float
     */
    public static function getAverageRating(int $entryId): float {
        $average = FeedbackRecord::find()
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
        $total = FeedbackRecord::find()
            ->where([
                'entryId' => $entryId,
                'feedbackStatus' => FeedbackStatus::Approved,
                'feedbackType' => FeedbackType::Review,
            ])
            ->andWhere(['not', ['rating' => null]])
            ->all();
        return count($total);
    }

    public static function getTotalPending(int $entryId): int {
        $pending = FeedbackRecord::find()
            ->where([
                'entryId' => $entryId,
                'feedbackStatus' => FeedbackStatus::Pending,
            ])
            ->all();
        return count($pending);
    }
}