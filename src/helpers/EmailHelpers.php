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
        if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            try {
                $mailer = Craft::$app->getMailer();

                $message = $mailer
                    ->compose($key, ['feedback' => $data])
                    ->setTo($data['email']);

                return $message->send();
                
            } catch (\Exception $e) {
                Craft::error('Failed to send feedback email: ' . $e->getMessage(), __METHOD__);
                return false;
            }
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