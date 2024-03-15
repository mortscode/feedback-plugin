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
 * ReviewStatsModel Model
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
class ReviewStatsModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int|null Average Rating
     */
    public ?int $averageRating;

    /**
     * @var int|null Total Ratings
     */
    public ?int $totalRatings;



    // Public Methods
    // =========================================================================

    /**
     * Define what is returned when model is converted to string
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->averageRating;
    }
}
