<?php
/**
 * Feedback plugin for Craft CMS 3.x
 *
 * Questions & Answers and Reviews & Ratings
 *
 * @link      https://owl-design.net
 * @copyright Copyright (c) 2018 Vadim Goncharov
 */

namespace mortscode\feedback\elements\actions;

use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\events\SetStatusEvent;
use mortscode\feedback\Feedback;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use yii\db\Exception;

/**
 * Class SetStatus
 *
 * @package owldesign\qarr\elements\actions
 *
 * @property-read mixed $triggerHtml
 * @property-read string $triggerLabel
 */
class SetStatus extends ElementAction
{
    // Properties
    // =========================================================================

    public const EVENT_AFTER_SAVE = 'eventAfterSave';

    /**
     * @var
     */
    public $status;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('feedback', 'Set Status');
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
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

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('feedback/_components/elementactions/SetStatus/trigger');
    }

    /**
     * @param ElementQueryInterface $query
     * @return bool
     * @throws Exception
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $response = Feedback::$plugin->feedbackService->updateSelectedFeedback($query->all(), $this->status);

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE)) {
            $this->trigger(self::EVENT_AFTER_SAVE, new SetStatusEvent([
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
}