<?php
/**
 * Feedback plugin for Craft CMS 3.x
 *
 * A comments and reviews plugin for Craft CMS 3.x
 *
 * @link      mortscode.com
 * @copyright Copyright (c) 2021 Scot Mortimer
 */

namespace mortscode\feedback\services;

use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\enums\FeedbackMessages;
use mortscode\feedback\enums\FeedbackOrigin;
use mortscode\feedback\Feedback;

use Craft;
use craft\base\Component;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\helpers\EmailHelpers;
use mortscode\feedback\records\FeedbackRecord;
use mortscode\feedback\helpers\RatingsHelpers;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * FeedbackService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Scot Mortimer
 * @package   Feedback
 * @since     1.0.0
 *
 * @property-read null|String $recaptchaKey
 * @property-read null|String $emailHeaderUrl
 * @property-read int $totalPendingFeedback
 * @property-read array $statusValues
 */
class FeedbackService extends Component
{
    /**
     * Get the feedback items belonging to an entry
     *
     * @param int $entryId
     * @return array [FeedbackElement]
     */
    public function getEntryFeedback(int $entryId): array
    {
        // get all records from DB related to entry
        $entryFeedback = FeedbackRecord::find()
            ->where(['entryId' => $entryId, 'feedbackStatus' => FeedbackStatus::Approved])
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();

        $feedbackElements = [];

        foreach ($entryFeedback as $feedbackRecord) {
            $feedbackElement = new FeedbackElement();
            $feedbackElement->setAttributes($feedbackRecord->getAttributes(), false);

            $feedbackElements[] = $feedbackElement;
        }

        return $feedbackElements;
    }

    /**
     * getFeedbackById
     *
     * @param mixed $feedbackId
     * @return FeedbackElement
     */
    public function getFeedbackById($feedbackId): FeedbackElement
    {
        // get one record from DB related to entry
        $feedbackRecord = FeedbackRecord::find()
            ->where(['id' => $feedbackId])
            ->one();

        $feedbackElement = new FeedbackElement();
        $feedbackElement->setAttributes($feedbackRecord->getAttributes(), false);

        return $feedbackElement;
    }

    /**
     * getStatusValues
     *
     * @return array
     */
    public function getStatusValues(): array
    {
        return [
            ucfirst(FeedbackStatus::Approved) => FeedbackStatus::Approved,
            ucfirst(FeedbackStatus::Pending) => FeedbackStatus::Pending,
            ucfirst(FeedbackStatus::Spam) => FeedbackStatus::Spam
        ];
    }

    /**
     * getRecaptchaKey
     *
     * @return String | null
     */
    public function getRecaptchaKey(): ?string
    {
        $settings = Feedback::$plugin->getSettings();
        $recaptchaKey = Craft::parseEnv($settings->recaptchaSiteKey);

        if ($recaptchaKey) {
            return Craft::parseEnv($settings->recaptchaSiteKey);
        }

        return null;
    }

    /**
     * getEmailHeaderUrl
     *
     * @return String | null
     */
    public function getEmailHeaderUrl(): ?string
    {
        $settings = Feedback::$plugin->getSettings();
        $emailHeaderUrl = Craft::parseEnv($settings->emailHeaderUrl);

        if ($emailHeaderUrl) {
            return Craft::parseEnv($settings->emailHeaderUrl);
        }

        return null;
    }

    /**
     * getLocationByIp
     *
     * @param string $ip
     * @return array|null
     */
    public function getLocationByIp(string $ip): ?array
    {
        $location = @json_decode(file_get_contents("https://ipinfo.io/{$ip}/json"), true);

        if ($location['bogon']) {
            return [];
        }

        return $location;
    }

    /**
     * getPendingFeedbackByType
     *
     * @param string $type
     * @return int
     */
    public function getPendingFeedbackByType(string $type): int
    {
        return FeedbackElement::find()
            ->where(['feedbackType' => $type, 'feedbackStatus' => FeedbackStatus::Pending])
            ->count();
    }

    /**
     * getTotalPendingFeedback
     *
     * @return int
     */
    public function getTotalPendingFeedback(): int
    {
        return FeedbackElement::find()
            ->where(['feedbackStatus' => FeedbackStatus::Pending])
            ->count();
    }

    /**
     * Update selected feedback statuses
     *
     * @param array $feedbackItems
     * @param string $status
     * @return bool
     * @throws \yii\db\Exception
     * @throws InvalidConfigException
     */
    public function updateSelectedFeedback(array $feedbackItems, string $status): bool
    {
        foreach ($feedbackItems as $feedback) {
            if ($feedback) {
                $this->_updateFeedbackStatus($feedback->id, $status);

                try {
                    Feedback::$plugin->feedbackService->updateEntryRatings($feedback->entryId);
                } catch (ElementNotFoundException | Exception | Throwable $e) {
                    Craft::error("Error updating Entry ratings");
                }
            } else {
                Craft::error("Can't update status");
            }
        }

        return true;
    }

    /**
     * Update single entry rating by id
     *
     * @param int|null $entryId
     * @return void
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function updateEntryRatings(?int $entryId): void
    {
        if (!$entryId) {
            return;
        }

        $entry = Entry::findOne($entryId);
        $hasAverageRating = isset($entry->averageRating);
        $hasTotalRatings = isset($entry->totalRatings);
        $hasTotalPending = isset($entry->totalPending);

        if ($entry) {
            if ($hasAverageRating) {
                $entry->setFieldValue('averageRating', RatingsHelpers::getAverageRating($entryId));
            }
            if ($hasTotalRatings) {
                $entry->setFieldValue('totalRatings', RatingsHelpers::getTotalRatings($entryId));
            }
            if ($hasTotalPending) {
                $entry->setFieldValue('totalPending', RatingsHelpers::getTotalPending($entryId));
            }
            if ($hasAverageRating || $hasTotalPending || $hasTotalRatings) {
                Craft::$app->elements->saveElement($entry, false, true, false);
            }
        }
    }

    /**
     * handleMailDelivery
     *
     * Takes in original feedback record, if it exists
     * Compares its values to the updated feedback element
     * Determines whether or not to send email
     *
     * @param bool $isNew
     * @param FeedbackRecord|null $feedback
     * @throws InvalidConfigException
     */
    public function handleMailDelivery(bool $isNew, ?FeedbackRecord $feedback): void
    {
        if (!$feedback) {
            return;
        }

        $emailData = [
            'name' => $feedback->name,
            'email' => $feedback->email,
            'comment' => $feedback->comment,
            'response' => $feedback->response,
            'feedbackType' => $feedback->feedbackType,
            'entryId' => $feedback->entryId,
            'rating' => $feedback->rating,
        ];

        // IF FEEDBACK IS NEW && FROM THE FRONTEND, SEND EMAIL
        if ($isNew) {
            if (!$feedback->feedbackOrigin == FeedbackOrigin::FRONTEND) {
                return;
            }

            !EmailHelpers::sendEmail(FeedbackMessages::MESSAGE_NEW_FEEDBACK, $emailData);

            return;
        }

        $feedbackApproved = $feedback->feedbackStatus == FeedbackStatus::Approved;
        $importedFeedback = $feedback->oldAttributes['feedbackOrigin'] == FeedbackOrigin::IMPORT_DISQUS;
        $responseUpdated = $feedback->oldAttributes['response'] !== $feedback->response;
        $approvedWithResponse = $feedback->response != null
            && $feedback->oldAttributes['feedbackStatus'] !== FeedbackStatus::Approved
            && $feedback == FeedbackStatus::Approved;

        // IF NOT IMPORTED, HAS AN EMAIL PROPERTY, AND IS APPROVED, CONSIDER EMAILING
        if (!$importedFeedback && $feedbackApproved) {
            // response has been updated since last save
            // or
            // response is newly approved and has a response
            if ($responseUpdated || $approvedWithResponse) {
                EmailHelpers::sendEmail(FeedbackMessages::MESSAGE_FEEDBACK_RESPONSE, $emailData);
            }
        }
    }

    /**
     * Update all entry ratings
     *
     * @return void
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function updateAllEntryRatings(): void
    {
        $entries = Entry::findAll();

        foreach ($entries as $entry) {
            $this->updateEntryRatings($entry->id);
        }
    }


    // PRIVATE METHODS ==================================
    // ==================================================
    /**
     * updateFeedbackStatus
     *
     * @param int $elementId
     * @param string $status
     * @return void
     * @throws \yii\db\Exception
     */
    private function _updateFeedbackStatus(int $elementId, string $status): void
    {
        if (!$elementId) {
            return;
        }

        Craft::$app->getDb()->createCommand()
            ->update('{{%feedback_record}}', ['feedbackStatus' => $status], ['id' => $elementId])
            ->execute();
    }
}
