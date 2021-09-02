<?php
/**
 * Feedback plugin for Craft CMS 3.x
 *
 * An entry feedback plugin
 *
 * @link      https://github.com/mortscode
 * @copyright Copyright (c) 2020 Scot Mortimer
 */

namespace mortscode\feedback\controllers;

use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\web\Request;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use LitEmoji\LitEmoji;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\enums\FeedbackOrigin;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\Feedback;
use mortscode\feedback\helpers\RequestHelpers;

use Craft;
use craft\web\Controller;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use GuzzleHttp\Client;
use SimpleXMLElement;

/**
 * Feedback Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Scot Mortimer
 * @package   Reviews
 * @since     1.0.0
 */
class FeedbackController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['save'];

    // Public Methods
    // =========================================================================

    /**
     * Save Action
     *
     * Save feedback item to the database
     *
     * @return Response|null
     * @throws BadRequestHttpException|MissingComponentException
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        if (!RequestHelpers::isCpRequest()) {

            // first, validate the Recaptcha success
            $validRecaptcha = $this->_verifyRecaptcha();

            if (!$validRecaptcha) {
                // error if ReCaptcha fails
                Craft::$app->getSession()->setError('Sorry, there was a problem validating the feedback. Please try again.');

                return null;
            }
        }

        // Determine whether we're creating or updating and get that model back
        $feedback = $this->_getFeedbackElementModel($request->getParam('feedbackId', null));

        // Populate the new element model
        $feedback = $this->_populateFeedbackElement($feedback, $request);

        // Validate the new FeedbackModel model
        $isValid = $feedback->validate();

        if ($isValid) {
            // IF VALID, LET'S TRY TO SAVE THE ELEMENT
            if (!Craft::$app->getElements()->saveElement($feedback)) {
                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'errors' => $feedback->getErrors(),
                    ]);
                }

                $this->setFailFlash(Craft::t('app', 'Couldn’t save feedback.'));

                return null;
            }
        } else {
            // review is not valid
            Craft::$app->getSession()->setError('Please check for form validation errors.');
            // pass feedback with errors back to template
            Craft::$app->getUrlManager()->setRouteParams([
                'feedback' => $feedback
            ]);

            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $feedback->getErrors(),
                ]);
            }

            return null;
        }

        // A nice JSON object of the new data
        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $feedback->id,
                'name' => $feedback->name,
                'email' => $feedback->email,
                'ipAddress' => $feedback->ipAddress,
                'userAgent' => $feedback->userAgent,
                'status' => $feedback->getStatus(),
                'rating' => $feedback->rating,
                'entryId' => $feedback->entryId,
                'comment' => $feedback->comment,
                'response' => $feedback->response,
                'feedbackType' => $feedback->feedbackType,
                'feedbackStatus' => $feedback->feedbackStatus,
                'feedbackOrigin' => $feedback->feedbackOrigin,
            ]);
        }

        // Ok, definitely valid + saved!
        $this->setSuccessFlash(Craft::t('feedback', 'Feedback saved'));

        return $this->redirectToPostedUrl($feedback);
    }

    /**
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws BadRequestHttpException
     */
    public function actionConvertFeedback(): ?Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user->admin) {
            return null;
        }

        $request = Craft::$app->getRequest();

        $feedbackId = $request->getRequiredParam('feedbackId');

        $feedbackElement = FeedbackElement::findOne($feedbackId);

        $feedbackType = $feedbackElement->feedbackType;

        // if feedback is a review, remove rating on conversion,
        // else set it to 5
        $feedbackElement->rating = $feedbackType == FeedbackType::Review
            ? null
            : 5;
        // if feedback is a review, change to question,
        // if feedback is a question, change to review
        $feedbackElement->feedbackType = $feedbackType == FeedbackType::Review
            ? FeedbackType::Question
            : FeedbackType::Review;

        // save updated feedback
        if (!Craft::$app->getElements()->saveElement($feedbackElement)) {

            $this->setFailFlash(Craft::t('app', 'Couldn’t convert feedback.'));

            return null;
        }

        return $this->redirect('/admin/feedback/' . $feedbackElement->entryId . '/' . $feedbackElement->id);
    }

    /**
     * Import XML data from Disqus
     *
     * @return void|Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     * @throws Exception
     * @throws \Throwable
     */
    public function actionImportXml()
    {
        $user = Craft::$app->getUser()->getIdentity();
        
        if (!$user->admin) {
            return;
        }

        $request = Craft::$app->getRequest();
     
        $data = $request->getRequiredParam('xml');

        $comments = new SimpleXMLElement($data);

        $threads = [];
        $posts = [];

        // loop over comments
        foreach($comments->thread as $thread) {

            $thread = [
                'id' => (string)$thread->attributes('http://disqus.com/disqus-internals')['id'],
                'url' => (string)$thread->link,
                'post_id' => (string)$thread->id,
                'comments' => [],
            ];

            // add comment thread to $threads array
            if ($thread['id']) {
                $threads[$thread['id']] = $thread;
            }
        }

        // loop over posts
        foreach($comments->post as $post) {

            $threadId = (string)$post->thread->attributes('http://disqus.com/disqus-internals')['id'];
            $postId = (string)$post->attributes('http://disqus.com/disqus-internals')['id'];
            
            if ($post->parent) {
                $parentId = (string)$post->parent->attributes('http://disqus.com/disqus-internals')['id'];
            } else {
                $parentId = null;
            }
          
            $posts[$postId] = [
                'id' => $postId,
                'created' => (string)$post->createdAt,
                'isDeleted' => (string)$post->isDeleted !== 'false',
                'isClosed' => (string)$post->isClosed !== 'false',
                'isSpam' => (string)$post->isSpam !== 'false',
                'name' => (string)$post->author->name,
                'user' => (string)$post->author->username,
                'message' => (string)$post->message,
                'children' => [],
            ];

            if ($parentId) {
                $posts[$parentId]['children'][] =& $posts[$postId];
            } else {
                $threads[$threadId]['comments'][] =& $posts[$postId];
            }
          
        }
        
        $threads = $this->_convertThreads($threads);

        $this->_createImportedRecords($threads);

        Craft::$app->getSession()->setNotice('Disqus XML imported');

        // Update the ratings values for all entries
        Feedback::$plugin->feedbackService->updateAllEntryRatings();

        // Ok, definitely valid + saved!
        return $this->redirect('feedback');
    }

    // PRIVATE METHODS
    // =========================

    /**
     * _convertComments
     *
     * Prepare the Disqus comments to be imported
     *
     * @param  mixed $comments
     * @return array
     */
    private function _convertComments(array $comments): array
    {

        $result = [];
        foreach($comments as $comment) {
        
            if($comment['isDeleted']) {
                continue;
            }

            if($comment['isSpam']) {
                continue;
            }

            $messageLinks = preg_match('/(http|ftp|mailto)/', $comment['message']);
            
            if($messageLinks) {
                continue;
            }

            // clean up html from text
            $formattedMessage = strip_tags($comment['message']);
            $formattedMessage = LitEmoji::shortcodeToUnicode($formattedMessage);
            $formattedMessage = htmlspecialchars_decode($formattedMessage);
        
            $newComment = [
                'created' => $comment['created'],
                'name' => $comment['name'],
                'disqusUser' => $comment['user'],
                'message' => $formattedMessage,
                'children' => $this->_convertComments($comment['children']),
            ];

            $result[] = $newComment;
      
        }

        return $result;
    }

    /**
     * _convertThreads
     *
     * Prepare the Disqus threads to be imported
     *
     * @param mixed $threads
     * @return array
     * @throws ExitException
     */
    private function _convertThreads(array $threads): array
    {
      
        $result = [];
        foreach($threads as $thread) {

            if (!$thread['url']) {
                continue;
            }
            
            if (!$thread['comments']) {
                continue;
            }

            // compare url to tmp url
            $tmpUrl = preg_match('/https:\/\/themodernproper.com\//', $thread['url']);
            
            // drop all the non-tmp urls
            if (!$tmpUrl) {
                continue;
            }

            $newComments = $this->_convertComments($thread['comments']);
            $slug = $this->_getSlugFromUrl($thread['url']);

            $newThread = [
                'id' => $thread['id'],
                'slug' => $slug,
                'post_id' => $thread['post_id'],
                'comments' => $newComments,
            ];

            // skip thread if comments list is empty
            if (empty($newThread['comments'])) {
                continue;
            }

            $result[] = $newThread;
        }
        
        return $result;
    }
    
    /**
     * _getSlugFromUrl
     *
     * @param  mixed $url
     * @return void
     */
    private function _getSlugFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $slug = $parsedUrl['path'];
        // remove "/" from string
        return substr($slug, 1);
    }

    /**
     * _createImportedRecords
     *
     * Create Feedback Records for the imported Disqus xml data
     *
     * @param mixed $threads
     * @return void
     * @throws MissingComponentException
     */
    private function _createImportedRecords(array $threads): void
    {
        $settings = Feedback::$plugin->getSettings();

        // loop through threads and match the URL
        foreach ($threads as $thread) {
            // find an entry with a matching uri
            $entry = Entry::find()
                ->section($settings->feedbackSections)
                ->slug($thread['slug'])
                ->one();

            if (!$entry) {
                // if no matching url, move on ->
                continue;
            }
            
            // otherwise let's add the feedback comments
            foreach ($thread['comments'] as $comment) {
                $existingRecord = FeedbackElement::find()
                    ->where(['comment' => $comment, 'entryId' => $entry['id']])
                    ->one();

                if ($existingRecord) {
                    continue;
                }
                
                $newFeedback = new FeedbackElement();
    
                $response = [];

                // look for response from admin
                if($settings->disqusUserHandle && $comment['children']) {
                    $children = $comment['children'];

                    foreach ($children as $child) {
                        if ($child['disqusUser'] === $settings['disqusUserHandle']) {
                            $response[] = $child['message'];
                        }
                    }
                }

                // get form fields
                $newFeedback->dateCreated = $comment['created'];
                $newFeedback->entryId = $entry->id ?? '';
                $newFeedback->name = $thread->name ?? $comment['name'];
                $newFeedback->email = $thread->email ?? '';
                $newFeedback->comment = $comment['message'] ?? 'MESSAGE ERROR';
                $newFeedback->response = $response[0] ?? null;
                $newFeedback->feedbackType = FeedbackType::Question;
                $newFeedback->feedbackOrigin = FeedbackOrigin::IMPORT_DISQUS;

                // review is valid, let's create the record
                try {
                    Craft::$app->getElements()->saveElement($newFeedback);
                } catch (ElementNotFoundException | \yii\base\Exception | \Throwable $e) {
                    // set error if save isn't successful
                    Craft::$app->getSession()->setError('Your feedback entry could not be saved. Please try again.');
                    // pass review back to template
                    Craft::$app->getUrlManager()->setRouteParams([
                        'feedback' => $newFeedback
                    ]);

                    return;
                }
            }
        }
    }

    /**
     * _getFeedbackElementModel
     * @param int|null $id
     * @return FeedbackElement()
     */
    private function _getFeedbackElementModel(?int $id): FeedbackElement
    {
        if ($id) {
            $feedback = FeedbackElement::findOne($id);
            return $feedback ?: new FeedbackElement();
        }

        return new FeedbackElement();
    }

    /**
     * @param string|null $origin
     * @return FeedbackElement
     */
    private function _handleFeedbackOrigin(?string $origin): string
    {
        if ($origin) {
            return $origin;
        }

        return RequestHelpers::isCpRequest() ? FeedbackOrigin::CONTROL_PANEL : FeedbackOrigin::FRONTEND;
    }

    /**
     * _populateFeedbackElement
     *
     * @param FeedbackElement $feedback
     * @param Request $request
     * @return FeedbackElement
     */
    private function _populateFeedbackElement(FeedbackElement $feedback, Request $request): FeedbackElement
    {
        if (!RequestHelpers::isCpRequest()) {
            // get IP and User Agent
            $feedback->ipAddress = $request->getUserIP();
            $feedback->userAgent = $request->getUserAgent();
        }

        // get form fields
        $feedback->rating = $request->getParam('rating', $feedback->rating);
        $feedback->entryId = $request->getParam('entryId', $feedback->entryId);
        $feedback->name = $request->getParam('name', $feedback->name);
        $feedback->email = $request->getParam('email', $feedback->email);
        $feedback->comment = $request->getParam('comment', $feedback->comment);
        $feedback->response = $request->getParam('response', $feedback->response);
        $feedback->feedbackType = $request->getParam('feedbackType', $feedback->feedbackType);
        $feedback->feedbackStatus = $request->getParam('feedbackStatus', $feedback->feedbackStatus);
        $feedback->feedbackOrigin = $this->_handleFeedbackOrigin($feedback->feedbackOrigin);

        return $feedback;
    }

    /**
     * _verifyRecaptcha
     * Return the 'success' value back from Recaptcha on post request
     * If no CP value in the "Recaptcha Secret Key" setting, return true
     *
     * @return bool
     * @throws GuzzleException
     */
    private function _verifyRecaptcha(): bool
    {
        $request = Craft::$app->getRequest();

        if (RequestHelpers::isCpRequest()) {
            return true;
        }

        $settings = Feedback::$plugin->getSettings();

        // if user has entered recaptcha keys, verify request
        if ($settings->recaptchaSecretKey) {

            $recaptchaSecret = Craft::parseEnv($settings->recaptchaSecretKey);

            $recaptchaToken = $request->getParam('token');

            $url = 'https://www.google.com/recaptcha/api/siteverify';

            $client = new Client();

            $response = $client->post($url, [
                'form_params' => [
                    'secret'   => $recaptchaSecret,
                    'response' => $recaptchaToken,
                    'remoteip' => $request->getUserIP(),
                ],
            ]);

            $result = json_decode((string)$response->getBody(), true);

            return $result['success'];
        }

        return true;
    }
}
