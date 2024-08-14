<?php
/**
 * Feedback plugin for Craft CMS 4.x
 *
 * Questions & Answers and Reviews & Ratings
 *
 */

namespace mortscode\feedback\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\events\SetFeedbackStatusEvent;
use mortscode\feedback\Feedback;

class SetFeedbackStatus extends SetStatus
{
    // Properties
    // ===========================================
    public const EVENT_AFTER_SAVE = 'eventAfterSave';

    // Public Methods
    // ===========================================
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('feedback/_components/elementactions/SetStatus/trigger');
    }

    /**
     * @throws \yii\db\Exception
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $response = Feedback::$plugin->feedbackService->updateSelectedFeedback($query->all(), $this->status);

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE)) {
            $this->trigger(self::EVENT_AFTER_SAVE, new SetFeedbackStatusEvent([
                'response' => $response,
                'status' => $this->status,
            ]));
        }

        if ($response) {
            $message = Craft::t('feedback', 'Status Updated');
        } else {
            $message = Craft::t('feedback', 'Failed to update status');
        }

        $this->setMessage($message);

        return $response;
    }

    // Protected Methods
    // ==================================================
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = [];
        $rules[] = [['status'], 'required'];
        $rules[] = [
            ['status'],
            'in',
            'range' => [
                FeedbackStatus::Approved,
                FeedbackStatus::Pending,
                FeedbackStatus::Spam,
            ]
        ];

        return $rules;
    }
}