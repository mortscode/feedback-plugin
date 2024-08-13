<?php

namespace mortscode\feedback\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Restore;
use craft\elements\actions\Delete;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use DateTime;
use LitEmoji\LitEmoji;
use mortscode\feedback\elements\db\FeedbackElementQuery;
use mortscode\feedback\enums\FeedbackOrigin;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\Feedback;
use mortscode\feedback\helpers\RequestHelpers;
use mortscode\feedback\models\FeedbackModel;
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
 * @property-read null|int $totalPendingCount
 * @property-read string $gqlTypeName
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
    public ?int $id;

    /**
     * @var int|null Entry ID
     */
    public $entryId;

    /**
     * @var DateTime|null Date created
     */
    public ?\DateTime $dateCreated;

    /**
     * @var DateTime|null Date updated
     */
    public ?\DateTime $dateUpdated;

    /**
     * name
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * email
     *
     * @var string|null
     */
    public ?string $email = null;

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
    public $feedbackStatus = null;

    /**
     * feedbackOrigin
     *
     * @var string|null
     */
    public $feedbackOrigin = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = LitEmoji::shortcodeToUnicode($this->name);
        $this->comment = LitEmoji::shortcodeToUnicode($this->comment);
        $this->response = LitEmoji::shortcodeToUnicode($this->response);
    }

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
                'key' => 'approved',
                'label' => 'Approved',
                'criteria' => [
                    'feedbackStatus' => FeedbackStatus::Approved,
                    'feedbackType' => [FeedbackType::Question, FeedbackType::Review]
                ]
            ],
            [
                'key' => 'pending',
                'label' => 'Pending',
                'badgeCount' => (new FeedbackElement)->getTotalPendingCount(),
                'criteria' => [
                    'feedbackStatus' => FeedbackStatus::Pending,
                    'feedbackType' => [FeedbackType::Question, FeedbackType::Review]
                ]
            ],
            [
                'key' => 'spam',
                'label' => 'Spam',
                'criteria' => [
                    'feedbackStatus' => FeedbackStatus::Spam,
                ]
            ],
            [
                'key' => 'allReviews',
                'label' => 'All Reviews',
                'badgeCount' => (new FeedbackElement)->getPendingCount(FeedbackType::Review),
                'criteria' => [
                    'feedbackType' => FeedbackType::Review,
                    'feedbackStatus' => [FeedbackStatus::Approved, FeedbackStatus::Pending],
                ]
            ],
            [
                'key' => 'allQuestions',
                'label' => 'All Questions',
                'badgeCount' => (new FeedbackElement)->getPendingCount(FeedbackType::Question),
                'criteria' => [
                    'feedbackType' => FeedbackType::Question,
                    'feedbackStatus' => [FeedbackStatus::Approved, FeedbackStatus::Pending],
                ]
            ],
            [
                'key' => 'allAnonymous',
                'label' => 'All Anonymous',
                'badgeCount' => (new FeedbackElement)->getPendingCount(FeedbackType::Rating),
                'criteria' => [
                    'feedbackType' => FeedbackType::Rating,
                    'feedbackStatus' => [FeedbackStatus::Approved, FeedbackStatus::Pending],
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
            'comment' => 'Comment',
            'feedbackType' => 'Type',
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
                break;
            case 'hasResponse':
                $vars = [
                  'response' => $this->response
                ];

                try {
                    return Craft::$app->getView()->renderTemplate('feedback/_elements/has-response', $vars);
                } catch (LoaderError | RuntimeError | SyntaxError | \yii\base\Exception $e) {
                    Craft::error('No response on this element');
                }
                break;
            case 'comment':
                $vars = [
                    'comment' => $this->comment
                ];

                try {
                    return Craft::$app->getView()->renderTemplate('feedback/_elements/table-comment', $vars);
                } catch (LoaderError | RuntimeError | SyntaxError | \yii\base\Exception $e) {
                    Craft::error('No comment on this element');
                }
                break;
            case 'feedbackType':
                return ucfirst($this->feedbackType);
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
     * @param string $attribute
     * @return string
     */
    protected function searchKeywords(string $attribute): string
    {
        $keywords = parent::searchKeywords($attribute);

        if ($attribute == 'comment') {
            $keywords = LitEmoji::shortcodeToUnicode($attribute);
        }

        return $keywords;
    }

    /**
     * @return string[]
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'entryTitle'];
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
        $rules[] = [
            'feedbackType',
            'required',
            'message' => 'Feedback Type is required'
        ];

        // NO RULES FOR DISQUS IMPORT
        if (FeedbackOrigin::IMPORT_DISQUS) {
            return $rules;
        }

        // DEFINE REQUIRED FIELDS FOR EACH TYPE OF FEEDBACK
        $rules[] = match ($this->feedbackType) {
            FeedbackType::Review => [
                ['name', 'email', 'rating'],
                'required',
                'message' => '{attribute} is required'
            ],
            FeedbackType::Question => [
                ['name', 'email', 'comment'],
                'required',
                'message' => '{attribute} is required'
            ],
        };

        // DEFINE FORMATTING RULES FOR FIELD TYPES
        $rules[] = ['email', 'email'];
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
    /**
     * @param array $sourceElements
     * @param string $handle
     * @return array|false|null
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|false|null
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

    // GraphQl
    // ------------------------------------
    /**
     * @param mixed $context
     * @return string
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'Feedback';
    }

    /**
     * @return string
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
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

            $encodedName = $this->name ? LitEmoji::encodeShortcode($this->name) : null;
            $encodedComment = LitEmoji::encodeShortcode($this->comment);
            $encodedResponse = LitEmoji::encodeShortcode($this->response);

            $feedbackRecord->entryId = $this->entryId;
            $feedbackRecord->name = $encodedName ?? 'Anonymous';
            $feedbackRecord->email = $this->email;
            $feedbackRecord->rating = match ($this->feedbackType) {
                FeedbackType::Rating => 5,
                FeedbackType::Review => $this->rating,
                FeedbackType::Question => null
            };
            $feedbackRecord->comment = $encodedComment;
            $feedbackRecord->response = $encodedResponse;
            $feedbackRecord->ipAddress = $this->ipAddress;
            $feedbackRecord->userAgent = $this->userAgent;
            $feedbackRecord->feedbackType = $this->feedbackType;
            $feedbackRecord->feedbackStatus = $this->feedbackStatus;
            $feedbackRecord->feedbackOrigin = $this->feedbackOrigin;
            $feedbackRecord->dateCreated = $this->dateCreated;

            if (!empty($feedbackRecord->email)) {
                Feedback::$plugin->feedbackService->handleMailDelivery($isNew, $feedbackRecord);
            }

            $feedbackRecord->save(true);
        }

//            CacheHelpers::purgeEntriesByUrl([$this->getEntry()->url]);
            parent::afterSave($isNew);
    }

    /**
     * afterDelete Event
     *
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        parent::afterDelete();
    }
}