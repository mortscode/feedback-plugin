<?php
/**
 * Feedback plugin for Craft CMS 3.x
 *
 * A comments and reviews plugin for Craft CMS 3.x
 *
 * @link      mortscode.com
 * @copyright Copyright (c) 2021 Scot Mortimer
 */

namespace mortscode\feedback;


use craft\base\Model;
use craft\events\RegisterEmailMessagesEvent;
use craft\models\SystemMessage;
use craft\services\SystemMessages;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\enums\FeedbackMessages;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\services\FeedbackService;
use mortscode\feedback\variables\FeedbackVariable;
use mortscode\feedback\models\Settings;
use mortscode\feedback\fields\FeedbackField;
use mortscode\feedback\widgets\FeedbackWidget;

use Craft;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use craft\services\Dashboard;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use mortscode\feedback\fields\AverageRating;
use mortscode\feedback\fields\TotalPending;
use mortscode\feedback\fields\TotalRatings;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Scot Mortimer
 * @package   Feedback
 * @since     1.0.0
 *
 * @property  FeedbackService $feedbackService
 * @property-read array $cpNavItem
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Feedback extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Feedback::$plugin
     *
     * @var Feedback
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Feedback::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Craft::$app->getView()->hook('cp.entries.edit.details', function(array $context) {

            $entry = $context['entry'];

            $settings = self::$plugin->getSettings();

            $selectedSections = [];
            foreach ($settings->feedbackSections as $section) {
                $selectedSections[] = $section;
            }

            $sectionIsSelected = in_array((string)$entry->section->handle, $selectedSections, true);

            if ($sectionIsSelected) {

                $reviewUrl = '/admin/feedback/create/' . FeedbackType::Review . '/' . $entry->id;
                $questionUrl = '/admin/feedback/create/' . FeedbackType::Question . '/' . $entry->id;

                return '<div class="flex"><a href="' . $reviewUrl . '" class="btn secondary">Add Review</a><a href="' . $questionUrl . '" class="btn secondary">Add Question</a></div>';
            }

        });

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'feedback/default';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['feedback/entries/<entryId:\d+>/<feedbackId:\d+>'] = ['template' => 'feedback/_feedback-detail'];
                $event->rules['feedback/create/<feedbackType:{slug}>/<entryId:\d+>'] = ['template' => 'feedback/_feedback-create'];
            }
        );

        // Register our elements
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = FeedbackElement::class;
            }
        );

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = AverageRating::class;
                $event->types[] = TotalRatings::class;
                $event->types[] = TotalPending::class;
            }
        );

        // Register our widgets
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = FeedbackWidget::class;
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('feedback', FeedbackVariable::class);
            }
        );

        // Register system emails
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, static function(RegisterEmailMessagesEvent $event) {
            $event->messages[] = new SystemMessage([
                'key' => FeedbackMessages::MESSAGE_NEW_FEEDBACK,
                'heading' => 'Feedback received on The Modern Proper',
                'subject' => 'Thanks for your feedback',
                'body' => file_get_contents(__DIR__ . '/emails/new_feedback.md'),
            ]);
        });

        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, static function(RegisterEmailMessagesEvent $event) {
            $event->messages[] = new SystemMessage([
                'key' => FeedbackMessages::MESSAGE_FEEDBACK_RESPONSE,
                'heading' => 'A Response from The Modern Proper',
                'subject' => 'A Response to your feedback',
                'body' => file_get_contents(__DIR__ . '/emails/feedback_response.md'),
            ]);
        });

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'feedback',
                '{name} plugin loaded',
                [
                    'name' => $this->name,
                ]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem(): array
    {
        $navItem = parent::getCpNavItem();
        $navItem['badgeCount'] = self::getInstance()->feedbackService->getTotalPendingFeedback();
        return $navItem;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'feedback/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
