<?php

namespace mortscode\feedback\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use mortscode\feedback\elements\db\FeedbackElementQuery;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\records\FeedbackRecord;
use yii\db\Exception;

/**
 * Class FeedbackElement
 *
 * @package mortscode\feedback\elements
 */
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

    /**
     * @return bool
     */
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

//        if ($this->feedbackStatus == FeedbackStatus::Approved) {
//            return FeedbackStatus::Approved;
//        }
//        if ($this->feedbackStatus == FeedbackStatus::Spam) {
//            return FeedbackStatus::Spam;
//        }

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

    /**
     * feedbackStatus
     *
     * @var string
     */
    public $feedbackStatus = FeedbackStatus::Pending;

    /**
     * isImport
     *
     * @var bool
     */
    public $isImport = false;

    protected function uiLabel(): ?string
    {
        return $this->name;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl("feedback/entries/$this->entryId/$this->id");
    }

    protected static function defineSources(string $context = null): array
    {
        function _getPending($feedbackType) {
            return '2';
        }

        return [
            [
                'key' => '*',
                'label' => 'All Feedback',
                'criteria' => []
            ],
            [
                'key' => 'reviews',
                'label' => 'Reviews',
                'badgeCount' => _getPending(FeedbackType::Review),
                'criteria' => [
                    'feedbackType' => FeedbackType::Review,
                ]
            ],
            [
                'key' => 'questions',
                'label' => 'Questions',
                'criteria' => [
                    'feedbackType' => FeedbackType::Question,
                ]
            ],
        ];
    }

    // TABLE ATTRIBUTES

    /**
     * @return string[]
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'name' => 'Name',
            'rating' => 'Rating',
            'dateCreated' => 'Created',
            'dateUpdated' => 'Updated',
        ];
    }

    /**
     * @param string $source
     * @return string[]
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'name',
            'rating',
            'dateCreated',
        ];
    }

    /**
     * @return array
     */
    protected static function defineSortOptions(): array
    {
        return [
            'name' => Craft::t('app', 'Name'),
            'rating' => Craft::t('app', 'Rating'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
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

    // VALIDATION RULES
    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] =
            [['name', 'feedbackType'],
                'required',
                'message' => '{attribute} is required'
            ];
        if (!$this->isImport) {
            $rules[] = ['email', 'required',
                    'message' => 'Email is required'
                ];
            $rules[] = ['email', 'email'];
        }
        $rules[] = ['comment', 'match',
            'pattern' => '%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',
            'not' => true,
            'message' => 'Your comment cannot contain urls or links.'
        ];

        return $rules;
    }

    // EVENTS
    // ------------------------------------
    /**
     * afterSave Event
     *
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            // Get the category record
            if (!$isNew) {
                $feedbackRecord = FeedbackRecord::findOne($this->id);

                if (!$feedbackRecord) {
                    throw new Exception('Invalid feedback ID: ' . $this->id);
                }
            } else {
                $feedbackRecord = new FeedbackRecord();
                $feedbackRecord->id = (int)$this->id;
            }

            $feedbackRecord->entryId = $this->entryId;
            $feedbackRecord->name = $this->name;
            $feedbackRecord->email = $this->email;
            $feedbackRecord->rating = $this->rating ?? null;
            $feedbackRecord->comment = $this->comment;
            $feedbackRecord->response = $this->response;
            $feedbackRecord->ipAddress = $this->ipAddress;
            $feedbackRecord->userAgent = $this->userAgent;
            $feedbackRecord->feedbackType = $this->feedbackType;
            $feedbackRecord->feedbackStatus = $this->feedbackStatus;
            $feedbackRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}