<?php

namespace mortscode\feedback\helpers;


use Craft;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\enums\FeedbackEmail;
use mortscode\feedback\enums\FeedbackEvents;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\records\FeedbackRecord;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

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
                $systemMessages = Craft::$app->getSystemMessages();
                $message = $systemMessages->getMessage($key);
                
                if (!$message) {
                    Craft::error("System message '{$key}' not found", __METHOD__);
                    return false;
                }

                $mailer = Craft::$app->getMailer();
                $view = Craft::$app->getView();
                
                // Render the message body with the feedback data
                $body = $view->renderString($message->body, ['feedback' => $data]);
                $subject = $view->renderString($message->subject, ['feedback' => $data]);

                // Convert markdown to HTML
                $htmlBody = Markdown::process($body);

                $email = $mailer
                    ->compose()
                    ->setTo($data['email'])
                    ->setSubject($subject)
                    ->setHtmlBody($htmlBody);

                return $email->send();
                
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