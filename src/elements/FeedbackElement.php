<?php

namespace mortscode\feedback\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Restore;
use craft\elements\actions\Delete;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use DateTime;
use mortscode\feedback\elements\db\FeedbackElementQuery;
use mortscode\feedback\enums\FeedbackOrigin;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\Feedback;
use mortscode\feedback\records\FeedbackRecord;
use mortscode\feedback\elements\actions\SetStatus;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\InvalidConfigException;
use yii\db\Exception;

/**
 * Class FeedbackElement
 *
 * @package mortscode\feedback\elements
 *
 * @property-read Entry $entry
 * @property-read string $entryTitle
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

    /**
     * @return bool
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @return array[]
     */
    public static function statuses(): array
    {
        return [
            FeedbackStatus::Approved => ['label' => ucfirst(FeedbackStatus::Approved), 'color' => 'green'],
            FeedbackStatus::Pending => ['label' => ucfirst(FeedbackStatus::Pending), 'color' => 'yellow'],
            FeedbackStatus::Spam => ['label' => ucfirst(FeedbackStatus::Spam), 'color' => 'red'],
        ];
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {

        if ($this->feedbackStatus == FeedbackStatus::Approved) {
            return FeedbackStatus::Approved;
        }
        if ($this->feedbackStatus == FeedbackStatus::Spam) {
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
     * @var DateTime|null Date created
     */
    public $dateCreated;

    /**
     * @var DateTime|null Date updated
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
     * feedbackOrigin
     *
     * @var string|null
     */
    public $feedbackOrigin = null;

    /**
     * @return string|null
     */
    protected function uiLabel(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl("feedback/$this->entryId/$this->id");
    }

    /**
     * @param string $type
     * @return int|null
     */
    public function getPendingCount(string $type): ?int
    {
        $pendingCount = Feedback::$plugin->feedbackService->getPendingFeedbackByType($type);
        return $pendingCount > 0 ? $pendingCount : null;
    }

    /**
     * @param string $type
     * @return int|null
     */
    public function getTotalPendingCount(): ?int
    {
        $pendingCount = Feedback::$plugin->feedbackService->getTotalPendingFeedback();
        return $pendingCount > 0 ? $pendingCount : null;
    }

    /**
     * @param string|null $context
     * @return array[]
     */
    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => 'All Feedback',
                'criteria' => []
            ],
            [
                'key' => 'allPending',
                'label' => 'All Pending',
                'badgeCount' => (new FeedbackElement)->getTotalPendingCount(),
                'criteria' => [
                    'feedbackStatus' => FeedbackStatus::Pending,
                ]
            ],
            [
                'key' => 'reviews',
                'label' => 'Reviews',
                'badgeCount' => (new FeedbackElement)->getPendingCount(FeedbackType::Review),
                'criteria' => [
                    'feedbackType' => FeedbackType::Review,
                ]
            ],
            [
                'key' => 'questions',
                'label' => 'Questions',
                'badgeCount' => (new FeedbackElement)->getPendingCount(FeedbackType::Question),
                'criteria' => [
                    'feedbackType' => FeedbackType::Question,
                ]
            ],
        ];
    }

    // TABLE ATTRIBUTES
    // ------------------------------------

    /**
     * @return string[]
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'name' => 'Name',
            'rating' => 'Rating',
            'recipe' => 'Recipe',
            'hasResponse' => 'Response',
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
            'dateCreated',
        ];
    }

    /**
     * @param string $attribute
     * @return string
     * @throws InvalidConfigException
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'recipe':
                $vars = [
                  'entryTitle' => $this->getEntry()->title,
                  'entryUrl' => $this->getEntry()->url,
                ];
                try {
                    return Craft::$app->getView()->renderTemplate('feedback/_elements/table-recipe', $vars);
                } catch (LoaderError | RuntimeError | SyntaxError | \yii\base\Exception $e) {
                    return $this->entryId;
                }
            case 'hasResponse':
                $vars = [
                  'response' => $this->response
                ];

                try {
                    return Craft::$app->getView()->renderTemplate('feedback/_elements/has-response', $vars);
                } catch (LoaderError | RuntimeError | SyntaxError | \yii\base\Exception $e) {
                    Craft::error('No response on this element');
                }
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @return array
     */
    protected static function defineSortOptions(): array
    {
        return [
            'name' => Craft::t('feedback', 'Name'),
            'rating' => Craft::t('feedback', 'Rating'),
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
    // ------------------------------------

    /**
     * @return string[]
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'comment', 'email', 'entryTitle'];
    }

    /**
     * @return ElementQueryInterface
     */
    public static function find(): ElementQueryInterface
    {
        return new FeedbackElementQuery(static::class);
    }

    /**
     * @return Entry
     */
    public function getEntry(): Entry
    {
        // Was the entry already eager-loaded?
        if (($entry = $this->getEagerLoadedElements('entry')) !== null) {
            return $entry[0];
        }

        return Craft::$app->entries->getEntryById($this->entryId);
    }

    /**
     * @return string
     */
    public function getEntryTitle(): string
    {
        return $this->getEntry()->title;
    }

    // VALIDATION RULES
    // ------------------------------------

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
        // IF ORIGIN IS FRONTEND OR CP, EMAIL IS REQUIRED
        if (in_array($this->feedbackOrigin,
            [
                FeedbackOrigin::CONTROL_PANEL,
                FeedbackOrigin::FRONTEND
            ], false))
        {
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

    // ACTIONS
    // ------------------------------------

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $elementsService = Craft::$app->getElements();

        // Delete
        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('feedback', 'Are you sure you want to delete the selected Feedback?'),
            'successMessage' => Craft::t('feedback', 'Feedback deleted.'),
        ]);

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('feedback', 'Feedback restored.'),
            'partialSuccessMessage' => Craft::t('feedback', 'Some Feedback restored.'),
            'failMessage' => Craft::t('feedback', 'Feedback not restored.'),
        ]);

        // Set Status
        $actions[] = SetStatus::class;

        return $actions;
    }

    // EAGER LOADING
    // ------------------------------------
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle === 'entry') {
            // get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select(['id as source', 'entryId as target'])
                ->from(['{{%feedback_record}}'])
                ->where([
                    'id' => $sourceElementIds
                ])
                ->all();

            return [
                'elementType' => Entry::class,
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    // EVENTS
    // ------------------------------------
    /**
     * afterSave Event
     *
     * @inheritdoc
     * @throws Exception if reasons
     * @throws InvalidConfigException
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
            $feedbackRecord->feedbackOrigin = $this->feedbackOrigin;
            $feedbackRecord->dateCreated = $this->dateCreated;

            Feedback::$plugin->feedbackService->handleMailDelivery($isNew, $feedbackRecord);

            $feedbackRecord->save(true);
        }

        try {
            Feedback::$plugin->feedbackService->updateEntryRatings($this->entryId);
        } catch (ElementNotFoundException | \yii\base\Exception | \Throwable $e) {
            Craft::error('Unable to update entry ratings after Element Save');
        }

        parent::afterSave($isNew);
    }

    /**
     * afterDelete Event
     *
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        try {
            Feedback::$plugin->feedbackService->updateEntryRatings($this->entryId);
        } catch (ElementNotFoundException | \yii\base\Exception | \Throwable $e) {
            Craft::error('Unable to update entry ratings after Element Delete');
        }

        parent::afterDelete();
    }
}