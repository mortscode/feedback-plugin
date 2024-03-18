<?php

namespace mortscode\feedback\migrations;

use craft\db\Migration;

/**
 * m240315_221541_nullify_name_email migration.
 */
class m240315_221541_nullify_name_email extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%feedback_record}}', 'email', "string(255)");
        $this->alterColumn('{{%feedback_record}}', 'name', "string(255)");
    }
    /**

     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m240315_221541_nullify_name_email cannot be reverted.\n";
        return false;
    }
}
