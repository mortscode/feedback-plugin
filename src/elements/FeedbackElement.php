<?php

namespace mortscode\feedback\elements;

use craft\base\Element;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use mortscode\feedback\elements\db\FeedbackElementQuery;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;
use yii\db\Exception;

class FeedbackElement extends Element
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Feedback';
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return 'Feedback';
    }

    // Has Content
    public static function hasContent(): bool
    {
        return true;
    }

    // Feedback Status Options
    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            FeedbackStatus::Approved => ['label' => ucfirst(FeedbackStatus::Approved), 'color' => 'green'],
            FeedbackStatus::Pending => ['label' => ucfirst(FeedbackStatus::Pending), 'color' => 'yellow'],
            FeedbackStatus::Spam => ['label' => ucfirst(FeedbackStatus::Spam), 'color' => 'red'],
        ];
    }

    public function getStatus(): string
    {

        if ($this->status === 'isApproved') {
            return FeedbackStatus::Approved;
        }
        if ($this->status === 'isSpam') {
            return FeedbackStatus::Spam;
        }

        return FeedbackStatus::Pending;
    }

    // PUBLIC VARIABLES
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Entry ID
     */
    public $entryId;

    /**
     * @var \DateTime|null Date created
     */
    public $dateCreated;

    /**
     * @var \DateTime|null Date updated
     */
    public $dateUpdated;

    /**
     * name
     *
     * @var string
     */
    public $name;

    /**
     * email
     *
     * @var string
     */
    public $email;

    /**
     * rating
     *
     * @var int
     */
    public $rating = null;

    /**
     * comment
     *
     * @var string
     */
    public $comment = null;

    /**
     * response
     *
     * @var string
     */
    public $response = null;

    /**
     * ipAddress
     *
     * @var string
     */
    public $ipAddress = null;

    /**
     * userAgent
     *
     * @var string
     */
    public $userAgent = null;

    /**
     * FeedbackType
     *
     * @var string
     */
    public $feedbackType = null;

    protected function uiLabel(): ?string
    {
        return $this->name;
    }

    public function getCpEditUrl(): UrlHelper
    {
        return UrlHelper::cpUrl("feedback/entries/$this->entryId/$this->id");
    }

    // AfterSave method

    /**
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            \Craft::$app->db->createCommand()
                ->insert('{{%feedback_record}}', [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                    'entryId' => $this->entryId,
                    'rating' => $this->rating,
                    'comment' => $this->comment,
                    'response' => $this->response,
                    'ipAddress' => $this->ipAddress,
                    'userAgent' => $this->userAgent,
                    'feedbackType' => $this->feedbackType,
                ])
                ->execute();
        } else {
            \Craft::$app->db->createCommand()
                ->update('{{%feedback_record}}', [
                    'name' => $this->name,
                    'email' => $this->email,
                    'rating' => $this->rating,
                    'comment' => $this->comment,
                    'response' => $this->response,
                    'feedbackType' => $this->feedbackType,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            ['key' => '*', 'label' => 'All Feedback', 'criteria' => []],
        ];
    }

    // TABLE ATTRIBUTES
    protected static function defineTableAttributes(): array
    {
        return [
            'name' => 'Name',
            'rating' => 'Rating',
            'dateCreated' => 'Created',
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'name',
            'rating',
            'dateCreated'
        ];
    }

    // SEARCHABLE DATA
    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'comment', 'email'];
    }

    public static function find(): ElementQueryInterface
    {
        return new FeedbackElementQuery(static::class);
    }

    protected static function defineActions(string $source = null): array
    {
        return [
            SetStatus::class,
        ];
    }
}