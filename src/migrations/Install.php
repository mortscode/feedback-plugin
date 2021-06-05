<?php
/**
 * Feedback plugin for Craft CMS 3.x
 *
 * A comments and reviews plugin for Craft CMS 3.x
 *
 * @link      mortscode.com
 * @copyright Copyright (c) 2021 Scot Mortimer
 */

namespace mortscode\feedback\migrations;

use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\Feedback;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * Feedback Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Scot Mortimer
 * @package   Feedback
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables(): bool
    {
        $tablesCreated = false;

    // feedback_record table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%feedback_record}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%feedback_record}}',
                [
                    'id' => $this->integer()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    // Custom columns in the table
                    'entryId' => $this->integer()->notNull(),
                    'name' => $this->string(255)->notNull(),
                    'email' => $this->string(255)->notNull(),
                    'rating' => $this->integer(),
                    'comment' => $this->text(),
                    'response' => $this->text(),
                    'ipAddress' => $this->string(),
                    'userAgent' => $this->string(),
                    'feedbackType' => $this->enum('feedbackType', [
                        FeedbackType::Review,
                        FeedbackType::Question
                    ]),
                    'feedbackStatus' => $this->enum('status', [
                        FeedbackStatus::Approved,
                        FeedbackStatus::Pending,
                        FeedbackStatus::Spam,
                    ]),
                    'PRIMARY KEY(id)',
                    'isImport' => $this->boolean(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes(): void
    {
        // feedback_record table
        $this->createIndex(null, '{{%feedback_record}}', 'entryId', false);
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys(): void
    {
        // feedback_record table foreign key
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%feedback_record}}', 'id'),
            '{{%feedback_record}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData(): void
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables(): void
    {
    // feedback_record table
        $this->dropTableIfExists('{{%feedback_record}}');
    }
}
