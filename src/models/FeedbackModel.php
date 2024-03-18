<?php
/**
 * Feedback plugin for Craft CMS 3.x
 *
 * An entry feedback plugin
 *
 * @link      https://github.com/mortscode
 * @copyright Copyright (c) 2020 Scot Mortimer
 */

namespace mortscode\feedback\models;

use mortscode\feedback\enums\FeedbackType;

use craft\base\Model;

/**
 * FeedbackModel Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    mortscode
 * @package   Feedback
 * @since     1.0.0
 */
class FeedbackModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public ?int $id;

    /**
     * @var int|null Entry ID
     */
    public ?int $entryId;

    /**
     * @var \DateTime|null Date created
     */
    public ?\DateTime $dateCreated;

    /**
     * @var \DateTime|null Date updated
     */
    public ?\DateTime $dateUpdated;

    /**
     * name
     *
     * @var string
     */
    public string $name;

    /**
     * email
     *
     * @var string
     */
    public string $email;

    /**
     * rating
     *
     * @var int
     */
    public ?int $rating = null;

    /**
     * comment
     *
     * @var string
     */
    public ?string $comment = null;

    /**
     * response
     *
     * @var string
     */
    public ?string $response = null;

    /**
     * ipAddress
     *
     * @var string
     */
    public ?string $ipAddress = null;

    /**
     * userAgent
     *
     * @var string
     */
    public ?string $userAgent = null;

    /**
     * feedbackType
     *
     * @var string
     */
    public string $feedbackType = FeedbackType::Review;


    // Public Methods
    // =========================================================================

//    /**
//     * Returns the validation rules for attributes.
//     *
//     * Validation rules are used by [[validate()]] to check if attribute values are valid.
//     * Child classes may override this method to declare different validation rules.
//     *
//     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
//     *
//     * @return array
//     */
//    protected function defineRules(): array
//    {
//        $rules = parent::defineRules();
//
//        // the feedbackType is required
//        $rules[] = [
//            'feedbackType',
//            'required',
//            'message' => 'Feedback Type is required'
//        ];
//
//        // the email attribute should be a valid email address
//        $rules[] = ['email', 'email'];
//
//        // the comment field should not have links in it
//        $rules[] = [
//            'comment',
//            'match',
//            'pattern' => '%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',
//            'not' => true,
//            'message' => 'Your comment cannot contain urls or links.'
//        ];
//
//        // conditionally require name and email on non-anonymous feedback types
//        if (!$this->feedbackType == FeedbackType::Rating) {
//            $rules[] = [
//                ['name', 'email'],
//                'required',
//                'message' => '{attribute} is required'
//            ];
//        }
//
//        return $rules;
//    }

    /**
     * Define what is returned when model is converted to string
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->rating;
    }
}
