<?php

namespace mortscode\feedback\migrations;

use Craft;
use craft\db\Migration;
use mortscode\feedback\enums\FeedbackType;

/**
 * m240316_053441_feedbacktype_enum_rating migration.
 */
class m240316_053441_feedbacktype_enum_rating extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $reviewType = FeedbackType::Review;
        $questionType = FeedbackType::Question;
        $ratingType = FeedbackType::Rating;

        $this->alterColumn('{{%feedback_record}}', 'feedbackType', "enum('$reviewType', '$questionType', '$ratingType')");
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m240316_053441_feedbacktype_enum_rating cannot be reverted.\n";
        return false;
    }
}
