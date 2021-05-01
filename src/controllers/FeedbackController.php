<?php
/**
 * Reviews plugin for Craft CMS 3.x
 *
 * An entry reviews plugin
 *
 * @link      https://github.com/mortscode
 * @copyright Copyright (c) 2020 Scot Mortimer
 */

namespace mortscode\feedback\controllers;

use craft\elements\Entry;
use craft\errors\MissingComponentException;
use GuzzleHttp\Exception\GuzzleException;
use mortscode\feedback\enums\FeedbackStatus;
use mortscode\feedback\enums\FeedbackType;
use mortscode\feedback\Feedback;
use mortscode\feedback\helpers\RatingsHelpers;
use mortscode\feedback\models\FeedbackModel;
use mortscode\feedback\models\QuestionModel;
use mortscode\feedback\models\ReviewModel;
use mortscode\feedback\Reviews;
use mortscode\feedback\records\FeedbackRecord;

use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use GuzzleHttp\Client;
use SimpleXMLElement;

/**
 * Reviews Controller
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
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        // first, validate the Recaptcha success
        $validRecaptcha = $this->_verifyRecaptcha();

        if (!$validRecaptcha) {
            // error if ReCaptcha fails
            Craft::$app->getSession()->setError('Sorry, there was a problem. Please try again.');

            return null;
        }

        // Create a new FeedbackModel model
        $feedback = $this->_setFeedbackFromPost();
        // Validate the new FeedbackModel model
        $isValid = $feedback->validate();

        if ($isValid) {
            // feedback is valid, let's create the record
            $createFeedback = Feedback::$plugin->feedbackService->createFeedbackRecord($feedback);

            // attempt to create feedback
            if (!$createFeedback) {
                // set error if save isn't successful
                Craft::$app->getSession()->setError('Your review could not be saved. Please try again.');
                // pass review back to template
                Craft::$app->getUrlManager()->setRouteParams([
                    'review' => $feedback
                ]);

                return null;
            }
        } else {
            // review is not valid
            Craft::$app->getSession()->setError('Please check for errors.');
            // pass review back to template
            Craft::$app->getUrlManager()->setRouteParams([
                'feedback' => $feedback
            ]);

            return null;
        }

        // Ok, definitely valid + saved!
        return $this->redirectToPostedUrl();
    }

    /**
     * Update Action
     *
     * Update the feedback status //TODO
     * add/edit a feedback response
     *
     * @return Response
     * @throws BadRequestHttpException|MissingComponentException
     */
    public function actionUpdate(): Response
    {
        $this->requirePostRequest();
        
        $request = Craft::$app->getRequest();
        $entryId = $request->getRequiredParam('entryId');
        $feedbackId = $request->getRequiredParam('feedbackId');

        $attributes[] = [
            'response' => Craft::$app->getRequest()->getParam('response') ?? '',
        ];

        Feedback::$plugin->feedbackService->updateFeedbackRecord($feedbackId, $attributes[0]);

        Craft::$app->getSession()->setNotice('Feedback updated');

        return $this->redirect('feedback/entries/' . $entryId);
    }

    /**
     * Action Delete
     *
     * Delete a feedback item using its $feedbackId
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionDelete(): Response
    {
        $request = Craft::$app->getRequest();
        $entryId = $request->getRequiredParam('entryId');
        $feedbackId = $request->getRequiredParam('feedbackId');

        Feedback::$plugin->feedbackService->deleteFeedbackById($feedbackId);

        Craft::$app->getSession()->setNotice(Craft::t('feedback', 'Feedback deleted.'));

        return $this->redirect('feedback');
    }

    /**
     * Import XML data from Disqus
     *
     * @return void|Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
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
     * @param  mixed $threads
     * @return array
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
                $existingRecord = FeedbackRecord::find()
                    ->where(['comment' => $comment, 'entryId' => $entry['id']])
                    ->one();

                if ($existingRecord) {
                    continue;
                }
                
                $newFeedback = new FeedbackModel();
    
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
                $newFeedback->entryId = $entry->id ?? '';
                $newFeedback->name = $thread->name ?? $comment['name'];
                $newFeedback->email = $thread->email ?? '';
                $newFeedback->comment = $comment['message'] ?? 'MESSAGE ERROR';
                $newFeedback->response = $response[0] ?? null;
                $newFeedback->feedbackType = FeedbackType::Question;
                
                // review is valid, let's create the record
                $createReview = Feedback::$plugin->feedbackService->createFeedbackRecord($newFeedback);
                
                if (!$createReview) {
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
     * _setFeedbackFromPost
     *
     * @return FeedbackModel
     * @throws BadRequestHttpException
     */
    private function _setFeedbackFromPost(): FeedbackModel
    {
        $request = Craft::$app->getRequest();

        $feedback = new FeedbackModel();

        // get IP and User Agent
        $feedback->ipAddress = $request->getUserIP();
        $feedback->userAgent = $request->getUserAgent();

        // get form fields
        $feedback->rating = $request->getParam('rating', $feedback->rating);
        $feedback->entryId = $request->getRequiredParam('entryId', $feedback->entryId);
        $feedback->name = $request->getRequiredParam('name', $feedback->name);
        $feedback->email = $request->getRequiredParam('email', $feedback->email);
        $feedback->comment = $request->getParam('comment', $feedback->comment);
        $feedback->feedbackType = $request->getParam('$this->feedbackType', $feedback->feedbackType);

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
        $settings = Feedback::$plugin->getSettings();

        // if user has entered recaptcha keys, verify request
        if ($settings->recaptchaSecretKey) {

            $recaptchaSecret = Craft::parseEnv($settings->recaptchaSecretKey);

            $request = Craft::$app->getRequest();
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
