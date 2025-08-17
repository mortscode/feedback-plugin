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

use mortscode\feedback\elements\db\FeedbackElementQuery;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\enums\FeedbackEvents;
use mortscode\feedback\enums\FeedbackMessages;
use mortscode\feedback\enums\FeedbackOrigin;
use mortscode\feedback\Feedback;

use Craft;
use craft\helpers\App;
use craft\base\Component;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\helpers\EmailHelpers;
use mortscode\feedback\helpers\RequestHelpers;
use mortscode\feedback\models\ReviewStatsModel;
use mortscode\feedback\records\FeedbackRecord;
use mortscode\feedback\helpers\RatingsHelpers;
use yii\base\InvalidConfigException;
use yii\db\Exception;

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
 * @property-read string $graphQlToken
 * @property-read array $statusValues
 */
class FeedbackService extends Component
{
    /**
     * Get the feedback items belonging to an entry
     *
     * @param int $entryId
     * @return FeedbackElementQuery [FeedbackElement]
     */
    public function getEntryFeedback(int $entryId): FeedbackElementQuery
    {
        return FeedbackElement::find()
            ->entryId($entryId)
            ->feedbackStatus(FeedbackStatus::Approved);
    }

    /**
     * getFeedbackById
     *
     * @param mixed $feedbackId
     * @return FeedbackElement|null
     */
    public function getFeedbackById(mixed $feedbackId): ?FeedbackElement
    {
        return FeedbackElement::findOne($feedbackId);
    }

    /**
     * @param $entryId
     * @return ReviewStatsModel
     */
    public function getEntryReviewStats($entryId): ReviewStatsModel
    {
        $reviewStats = new ReviewStatsModel();
        $reviewStats->averageRating = RatingsHelpers::getAverageRating($entryId);
        $reviewStats->totalRatings = RatingsHelpers::getTotalRatings($entryId);

        return $reviewStats;
    }

    /**
     * @param $entryId
     * @param $ip
     *
     * @return bool
     */
    public function hasRatedToday($entryId, $ip): bool
    {
        return RequestHelpers::isRepeatAnonymousRating($entryId, $ip);
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
     * getRecaptchaEntSiteKey
     *
     * @return String | null
     */
    public function getRecaptchaEntSiteKey(): ?string
    {
        $settings = Feedback::$plugin->getSettings();
        $recaptchaKey = App::parseEnv($settings->recaptchaEntSiteKey);

        if ($recaptchaKey) {
            return App::parseEnv($settings->recaptchaEntSiteKey);
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
        $emailHeaderUrl = App::parseEnv($settings->emailHeaderUrl);

        if ($emailHeaderUrl) {
            return App::parseEnv($settings->emailHeaderUrl);
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
        $location = @json_decode(file_get_contents("https://ipinfo.io/". $ip. "/json"), true);

        if (isset($location['bogon'])) {
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
            ->feedbackType($type)
            ->feedbackStatus(FeedbackStatus::Pending)
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
            ->feedbackStatus(FeedbackStatus::Pending)
            ->count();
    }

    /**
     * getGraphQlToken
     *
     * @return string
     */
    public function getGraphQlToken(): string
    {
        return Feedback::$plugin->getSettings()->graphQlToken;
    }

    /**
     * Update selected feedback statuses
     *
     * @param array $feedbackItems
     * @param string $status
     * @return bool
     * @throws Exception
     */
    public function updateSelectedFeedback(array $feedbackItems, string $status): bool
    {
        foreach ($feedbackItems as $feedback) {
            if ($feedback) {
                $feedback->feedbackStatus = $status;
                Craft::$app->getElements()->saveElement($feedback);
            } else {
                Craft::error("Can't update status");
            }
        }

        return true;
    }

    /**
     * handleMailDelivery
     *
     * Takes in original feedback record, if it exists
     * Compares its values to the updated feedback element
     * Determines whether to send email
     *
     * @param bool $isNew
     * @param FeedbackRecord|FeedbackElement|null $feedback
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
            'feedbackStatus' => $feedback->feedbackStatus,
            'entryId' => $feedback->entryId,
            'rating' => $feedback->rating,
        ];

        // IF FEEDBACK IS NOT ORIGINALLY FROM THE FRONTEND, RETURN
        if ($feedback->feedbackOrigin !== FeedbackOrigin::FRONTEND) {
            return;
        }

        if ($isNew) {
            // EMAIL: NEW FEEDBACK
            !EmailHelpers::sendEmail(FeedbackMessages::MESSAGE_NEW_FEEDBACK, $emailData);
        } else {
            $feedbackEvent = EmailHelpers::getFeedbackEvent($feedback);

            if (
                $feedbackEvent == FeedbackEvents::ResponseAndApproved
                or $feedbackEvent == FeedbackEvents::NewResponse
            ) {
                EmailHelpers::sendEmail(FeedbackMessages::MESSAGE_FEEDBACK_RESPONSE, $emailData);
            }

            if ($feedbackEvent == FeedbackEvents::NewApproval) {
                EmailHelpers::sendEmail(FeedbackMessages::MESSAGE_FEEDBACK_APPROVED, $emailData);
            }
        }
    }
}
