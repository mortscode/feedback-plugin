<?php

namespace mortscode\feedback\helpers;


use Craft;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\records\FeedbackRecord;

class RequestHelpers
{
    /**
     * Returns whether a request is from the CP
     *
     * @return bool
     */
    public static function isCpRequest(): bool {
        $request = Craft::$app->getRequest()->isCpRequest;
        $userIsAdmin = Craft::$app->getUser()->getIsAdmin();

        return $request && $userIsAdmin;
    }

    /**
     * Check if user has already left an anonymous rating today
     *
     * @param FeedbackRecord $feedback
     * @return bool
     */
    public static function isRepeatAnonymousRating(int $entryId, string $ip): bool {
        $time = new \DateTime('now');
        $today = $time->format('Y-m-d');

        return FeedbackRecord::find()
            ->where([
                'ipAddress' => $ip,
                'entryId' => $entryId,
                'feedbackType' => FeedbackType::Rating
            ])
            ->andWhere(['>=', 'dateCreated', $today])
            ->exists();
    }
}