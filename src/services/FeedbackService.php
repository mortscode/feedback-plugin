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
use mortscode\feedback\Feedback;

use Craft;
use craft\base\Component;
use mortscode\feedback\models\FeedbackModel;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\records\FeedbackRecord;
use mortscode\feedback\helpers\RatingsHelpers;
use Throwable;
use yii\base\Exception;
use yii\db\StaleObjectException;

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
 * @property-read array $statusValues
 */
class FeedbackService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * createFeedbackRecord
     *
     * @param $feedback FeedbackElement
     * @return bool
     */
    public function createFeedbackRecord(FeedbackElement $feedback): bool
    {
//        craft::dd($feedback);
//        $feedbackRecord = new FeedbackElement();
//        $feedbackRecord->entryId = $feedback->entryId;
//        $feedbackRecord->name = $feedback->name;
//        $feedbackRecord->email = $feedback->email;
//        $feedbackRecord->rating = $feedback->rating ?? null;
//        $feedbackRecord->comment = $feedback->comment;
//        $feedbackRecord->response = $feedback->response;
//        $feedbackRecord->ipAddress = $feedback->ipAddress;
//        $feedbackRecord->userAgent = $feedback->userAgent;
//        $feedbackRecord->feedbackType = $feedback->feedbackType;
//        $feedbackRecord->feedbackStatus = $feedback->feedbackStatus;

        // save record in DB
        return Craft::$app->getElements()->saveElement($feedback);
//        return $feedbackRecord->save();
    }

    /**
     * updateFeedbackRecord
     *
     * @param int $feedbackId
     * @param $attributes
     * @return bool
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function updateFeedbackRecord(int $feedbackId, $attributes): bool
    {
        $feedbackRecord = FeedbackRecord::find()
            ->where(['id' => $feedbackId])
            ->one();
        $feedbackRecord->response = $attributes['response'];
        $feedbackRecord->feedbackStatus = $attributes['feedbackStatus'];

        // save record in DB
        $recordSaved = $feedbackRecord->save();

        // update the ratings fields
        if ($recordSaved) {
            $entryId = $feedbackRecord->entryId;
            $entry = Entry::findOne($entryId);
            if ($entry) {
                $entry->setFieldValue('averageRating', RatingsHelpers::getAverageRating($entryId));
                $entry->setFieldValue('totalRatings', RatingsHelpers::getTotalRatings($entryId));
                $entry->setFieldValue('totalPending', RatingsHelpers::getTotalPending($entryId));
                Craft::$app->elements->saveElement($entry, false, true, false);
            }
        }

        return $recordSaved;
    }

    /**
     * deleteFeedback
     *
     * @param int $feedbackId
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function deleteFeedbackById(int $feedbackId): void
    {
        // get record from DB
        $feedbackRecord = FeedbackRecord::find()
            ->where(['id' => $feedbackId])
            ->one();

        // if record exists then delete
        if ($feedbackRecord) {
            // delete record from DB
            $feedbackRecord->delete();
        }

        // log reset
        Craft::warning(Craft::t('feedback', 'Feedback with ID {feedbackId} reset by {username}', [
            'feedbackId' => $feedbackId,
            'username' => Craft::$app->getUser()->getIdentity()->username,
        ]), 'Feedback');
    }

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
            ->orderBy('dateCreated')
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
        } else {
            return null;
        }
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
     * getPendingFeedback
     *
     * @param string $type
     * @return int
     */
    public function getPendingFeedback(string $type): int
    {
        return FeedbackElement::find()
            ->where(['feedbackType' => $type, 'feedbackStatus' => FeedbackStatus::Pending])
            ->count();
    }

    /**
     * Update selected feedback statuses
     *
     * @param array $feedbackItems
     * @param string $status
     * @return bool
     * @throws \yii\db\Exception
     */
    public function updateSelectedFeedback(array $feedbackItems, string $status): bool
    {
        foreach ($feedbackItems as $key => $feedback) {
            if ($feedback) {
                $this->_updateFeedbackStatus($feedback->id, $status);
            } else {
                Craft::error("Can't update status");
            }
        }

        return true;
    }

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
