<?php
/**
 * Feedback plugin for Craft CMS 3.x
 *
 * A comments and reviews plugin for Craft CMS 3.x
 *
 * @link      mortscode.com
 * @copyright Copyright (c) 2021 Scot Mortimer
 */

namespace mortscode\feedback\variables;

use craft\errors\ElementNotFoundException;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\Feedback;

use Craft;
use mortscode\feedback\models\FeedbackModel;
use Throwable;
use yii\db\Exception;

/**
 * Feedback Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.feedback }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Scot Mortimer
 * @package   Feedback
 * @since     1.0.0
 */
class FeedbackVariable
{
    // Public Methods
    // =========================================================================

    /**
     * createFeedbackRecord
     *
     * @param FeedbackModel $feedback
     * @return bool
     */
    public function createFeedbackRecord(FeedbackModel $feedback): bool
    {
        return Feedback::$plugin->feedbackService->createFeedbackRecord($feedback);
    }

    /**
     * updateFeedbackRecord
     *
     * @param int $feedbackId
     * @param array $attributes
     * @return bool
     */
    public function updateFeedbackRecord(int $feedbackId, array $attributes): bool
    {
        return Feedback::$plugin->feedbackService->updateFeedbackRecord($feedbackId, $attributes);
    }

    /**
     * getEntryFeedback
     *
     * @param int $entryId
     * @return array[FeedbackElement]
     */
    public function getEntryFeedback(int $entryId): array
    {
        return Feedback::$plugin->feedbackService->getEntryFeedback($entryId);
    }

    /**
     * getFeedbackById
     *
     * @param  mixed $feedbackId
     * @return FeedbackElement
     */
    public function getFeedbackById($feedbackId): FeedbackElement
    {
        return Feedback::$plugin->feedbackService->getFeedbackById($feedbackId);
    }

    /**
     * getStatusOptions
     *
     * @return array
     */
    public function getStatusValues(): array
    {
        return Feedback::$plugin->feedbackService->getStatusValues();
    }

    /**
     * getReCaptchaKey
     *
     * @return string
     */
    public function getRecaptchaKey(): ?string
    {
        return Feedback::$plugin->feedbackService->getRecaptchaKey();
    }

    /**
     * getReCaptchaKey
     *
     * @return string
     */
    public function getEmailHeaderUrl(): ?string
    {
        return Feedback::$plugin->feedbackService->getEmailHeaderUrl();
    }

    /**
     * getLocationByIp
     *
     * @param string $ip
     * @return array
     */
    public function getLocationByIp(string $ip): array
    {
        return Feedback::$plugin->feedbackService->getLocationByIp($ip);
    }

    /**
     * getPendingFeedbackByType
     *
     * @param string $type
     * @return int
     */
    public function getPendingFeedbackByType(string $type): int
    {
        return Feedback::$plugin->feedbackService->getPendingFeedbackByType($type);
    }

    /**
     * getTotalPendingFeedback
     *
     * @return int
     */
    public function getTotalPendingFeedback(): int
    {
        return Feedback::$plugin->feedbackService->getTotalPendingFeedback();
    }

    /**
     * updateSelectedFeedback()
     * Update array of feedback items' status
     *
     * @param array $feedbackItems
     * @param string $status
     * @return int|null
     * @throws \yii\base\Exception
     */
    public function updateSelectedFeedback(array $feedbackItems, string $status): ?int
    {
        return Feedback::$plugin->feedbackService->updateSelectedFeedback($feedbackItems, $status);
    }
}
