<?php

namespace mortscode\feedback\helpers;


use Craft;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\enums\FeedbackEmail;
use mortscode\feedback\enums\FeedbackEvents;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\records\FeedbackRecord;
use yii\base\InvalidConfigException;

class EmailHelpers
{
    /**
     * Send a Feedback Email
     *
     * @param string $key
     * @param array $data
     * @return bool
     * @throws InvalidConfigException
     */
    public static function sendEmail(string $key, array $data = []): bool {
        if ($data['email']) {
            $mailer = Craft::$app->getMailer();

            $message = $mailer
                ->composeFromKey($key, ['feedback' => $data])
                ->setTo($data['email']);

            $message->send();

            return true;
        }

        return false;
    }

    /**
     * @param FeedbackRecord $feedback
     * @return string | null
     */
    public static function getFeedbackEvent(FeedbackRecord $feedback): ?string {
        // NEW RESPONSE, NEW APPROVAL, OR BOTH ***
        $feedbackNowApproved = $feedback->feedbackStatus == FeedbackStatus::Approved
            and $feedback->oldAttributes['feedbackStatus'] !== FeedbackStatus::Approved;
        $responseUpdated = $feedback->response !== ''
            and $feedback->oldAttributes['response'] !== $feedback->response;

        // RETURN BOTH
        if ($feedbackNowApproved and $responseUpdated) {
            return FeedbackEvents::ResponseAndApproved;
        }

        // RETURN NEW APPROVAL
        if ($feedbackNowApproved and !$responseUpdated) {
            return FeedbackEvents::NewApproval;
        }

        // RETURN NEW RESPONSE
        if ($responseUpdated) {
            return FeedbackEvents::NewResponse;
        }


        return null;
    }
}