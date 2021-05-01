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
     * feedbackType
     *
     * @var string
     */
    public $feedbackType = FeedbackType::Review;


    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // the name, email attributes are required
            [
                ['name', 'email', 'feedbackType'],
                'required',
                'message' => '{attribute} is required'
            ],

            // the email attribute should be a valid email address
            ['email', 'email'],

            // the comment field should not have links in it
            ['comment', 'match',
                'pattern' => '%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i',
                'not' => true,
                'message' => 'Your comment cannot contain urls or links.'
            ],
        ];
    }

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
