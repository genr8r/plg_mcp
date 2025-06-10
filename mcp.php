<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.MCP
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Component\Categories\Administrator\Model\CategoryModel;
use Joomla\Component\Content\Administrator\Model\ArticleModel;
use Joomla\CMS\Application\ApplicationHelper;

/**
 * MCP System Plugin
 */
class PlgSystemMcp extends CMSPlugin
{
    /**
     * @var \Joomla\CMS\Application\CMSApplication
     */
    protected $app;

    /**
     * @var string The bearer token from plugin parameters.
     */
    protected $bearerToken;

    /**
     * Plugin constructor.
     *
     * @param   object  &$subject  The object to observe
     * @param   array   $config    An optional associative array of configuration settings.
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->bearerToken = $this->params->get('bearer_token');
    }

    /**
     * Event triggered after the framework has been initialised.
     */
    public function onAfterInitialise()
    {
        $input = $this->app->input;
        $task  = $input->getString('task', '');

        // Only proceed if a task for this plugin is requested
        if (strpos($task, 'mcp.') !== 0) {
            return;
        }

        // Check for Bearer Token
        if (!$this->isAuthorized()) {
            $this->sendResponse(['error' => 'Unauthorized'], 401);
        }

        $action = substr($task, strlen('mcp.'));
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'get_article':
                $this->getArticle($data);
                break;
            case 'get_articles':
                $this->getJoomlaArticles();
                break;
            case 'get_categories':
                $this->getJoomlaCategories();
                break;
            case 'create_article':
                $this->createArticle($data);
                break;
            case 'manage_article_state':
                $this->manageArticleState($data);
                break;
            case 'move_article_to_trash':
                $this->moveArticleToTrash($data);
                break;
            case 'update_article':
                $this->updateArticle($data);
                break;
            default:
                $this->sendResponse(['error' => 'Invalid task'], 400);
                break;
        }
    }

    /**
     * Checks if the request is authorized.
     *
     * @return  bool
     */
    private function isAuthorized()
    {
        if (empty($this->bearerToken)) {
            return false;
        }

        $authHeader = $this->app->input->server->get('HTTP_AUTHORIZATION', '');
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1] === $this->bearerToken;
        }

        return false;
    }

    /**
     * Sends a JSON response and terminates the application.
     *
     * @param   mixed  $data      The data to encode as JSON.
     * @param   int    $statusCode The HTTP status code.
     */
    private function sendResponse($data, $statusCode = 200)
    {
        $this->app->setHeader('Content-Type', 'application/json');
        $this->app->setBody(json_encode($data));
        $this->app->sendHeaders();
        echo $this->app->getBody();
        $this->app->close();
    }

    /**
     * Retrieves all Joomla articles.
     */
    private function getJoomlaArticles()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'alias', 'catid', 'state', 'created']))
            ->from($db->quoteName('#__content'));
        $db->setQuery($query);
        $articles = $db->loadAssocList();

        $this->sendResponse(['data' => $articles]);
    }

    /**
     * Retrieves all Joomla categories.
     */
    private function getJoomlaCategories()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'alias']))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'));
        $db->setQuery($query);
        $categories = $db->loadAssocList();

        $this->sendResponse(['data' => $categories]);
    }

    /**
     * Creates a new article.
     *
     * @param   array  $data  The article data.
     */
    private function createArticle($data)
    {
        if (empty($data['title']) || empty($data['articletext']) || empty($data['catid'])) {
            $this->sendResponse(['error' => 'Missing required fields: title, articletext, catid'], 400);
        }

        $articleModel = new ArticleModel();
        $articleData = [
            'title'       => $data['title'],
            'alias'       => ApplicationHelper::stringURLSafe($data['title']),
            'articletext' => $data['articletext'], // Assuming HTML input as per Joomla's editor standard
            'catid'       => (int) $data['catid'],
            'state'       => isset($data['published']) && $data['published'] ? 1 : 0,
            'language'    => '*',
        ];

        try {
            if (!$articleModel->save($articleData)) {
                $this->sendResponse(['error' => $articleModel->getError()], 500);
            }
            $this->sendResponse(['success' => 'Article created successfully.', 'id' => $articleModel->getItem()->id]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Manages the state of an article.
     *
     * @param   array  $data  The state management data.
     */
    private function manageArticleState($data)
    {
        if (!isset($data['article_id']) || !isset($data['target_state'])) {
            $this->sendResponse(['error' => 'Missing required fields: article_id, target_state'], 400);
        }

        $articleId = (int) $data['article_id'];
        $targetState = (int) $data['target_state'];
        $validStates = [1, 0, 2, -2]; // published, unpublished, archived, trashed

        if (!in_array($targetState, $validStates)) {
            $this->sendResponse(['error' => 'Invalid target state'], 400);
        }

        $articleModel = new ArticleModel();
        try {
            $articleModel->publish([$articleId], $targetState);
            $this->sendResponse(['success' => "Article state changed to {$targetState}."]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Moves an article to the trash.
     *
     * @param   array  $data  The article data.
     */
    private function moveArticleToTrash($data)
    {
         if (!isset($data['article_id'])) {
            $this->sendResponse(['error' => 'Missing required field: article_id'], 400);
        }
        
        $stateData = [
            'article_id' => $data['article_id'],
            'target_state' => -2 // Trashed state
        ];
        
        $this->manageArticleState($stateData);
    }

    /**
     * Updates an existing article.
     *
     * @param   array  $data  The article update data.
     */
    private function updateArticle($data)
    {
        if (!isset($data['article_id'])) {
            $this->sendResponse(['error' => 'Missing required field: article_id'], 400);
        }

        $articleId = (int) $data['article_id'];
        $updateData = ['id' => $articleId];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
            $updateData['alias'] = ApplicationHelper::stringURLSafe($data['title']);
        }
        if (isset($data['introtext'])) {
            $updateData['introtext'] = $data['introtext'];
        }
        if (isset($data['fulltext'])) {
             $updateData['fulltext'] = $data['fulltext'];
        }
        if (isset($data['articletext'])) { // Handles cases where intro and fulltext are combined
            $updateData['articletext'] = $data['articletext'];
        }
        if (isset($data['metadesc'])) {
            $updateData['metadesc'] = $data['metadesc'];
        }
        if (isset($data['catid'])) {
            $updateData['catid'] = (int) $data['catid'];
        }

        $articleModel = new ArticleModel();
        try {
            if (!$articleModel->save($updateData)) {
                $this->sendResponse(['error' => $articleModel->getError()], 500);
            }
            $this->sendResponse(['success' => 'Article updated successfully.']);
        } catch (Exception $e) {
            $this->sendResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieves a single Joomla article by ID.
     *
     * @param   array  $data  The article data containing the article_id.
     */
    private function getArticle($data)
    {
        if (!isset($data['article_id'])) {
            $this->sendResponse(['error' => 'Missing required field: article_id'], 400);
        }

        $articleId = (int) $data['article_id'];

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'alias', 'catid', 'state', 'created', 'introtext', 'fulltext']))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($articleId));

        $db->setQuery($query);

        try {
            $article = $db->loadAssoc();

            if (!$article) {
                $this->sendResponse(['error' => 'Article not found'], 404);
            }

            $this->sendResponse(['data' => $article]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => $e->getMessage()], 500);
        }
    }
}