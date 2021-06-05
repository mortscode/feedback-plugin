<?php

namespace mortscode\feedback\helpers;


use Craft;
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
}