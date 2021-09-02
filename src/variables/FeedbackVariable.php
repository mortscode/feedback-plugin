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


use craft\fields\Number;
use mortscode\feedback\elements\db\FeedbackElementQuery;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\Feedback;
use mortscode\feedback\models\ReviewStatsModel;
use PhpParser\Node\Expr\Cast\Int_;


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
     * getEntryFeedback
     *
     * @param int $entryId
     * @return FeedbackElementQuery [FeedbackElement]
     */
    public function getEntryFeedback(int $entryId): FeedbackElementQuery
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
     * @param $entryId
     * @return ReviewStatsModel
     */
    public function getEntryReviewStats($entryId): ReviewStatsModel
    {
        return Feedback::$plugin->feedbackService->getEntryReviewStats($entryId);
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
