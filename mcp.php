<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

require_once __DIR__ . '/src/Auth/TokenAuthenticator.php';
require_once __DIR__ . '/src/Response/JsonResponder.php';
require_once __DIR__ . '/src/Handler/BaseHandler.php';
require_once __DIR__ . '/src/Handler/ArticleHandler.php';
require_once __DIR__ . '/src/Handler/CategoryHandler.php';
require_once __DIR__ . '/src/Handler/TagHandler.php';
require_once __DIR__ . '/src/Validator/InputValidator.php';

use MCPPlugin\Auth\TokenAuthenticator;
use MCPPlugin\Response\JsonResponder;
use MCPPlugin\Handler\ArticleHandler;
use MCPPlugin\Handler\CategoryHandler;
use MCPPlugin\Handler\TagHandler;

class PlgSystemMcp extends CMSPlugin
{
    protected $app;

    public function onAfterInitialise()
    {
        $input = $this->app->input;
        $task = $input->getString('task', '');

        if (strpos($task, 'mcp.') !== 0) {
            return;
        }

        // Authenticate using API token
        $authenticator = new TokenAuthenticator($this->app);
        $user = $authenticator->authenticate();
        
        if (!$user) {
            $responder = new JsonResponder($this->app);
            $responder->unauthorized('Authentication required. Provide X-Joomla-Token header.');
            return;
        }

        $action = substr($task, strlen('mcp.'));
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $responder = new JsonResponder($this->app);

        if ($action === 'info') {
            $responder->success([
                'plugin' => 'MCP - Model Context Protocol for Joomla',
                'version' => '2.0.0',
                'description' => 'AI-optimized content management API',
                'status' => 'SUCCESS - Authenticated!',
                'authenticated_as' => $user->username,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            return;
        }

        // Route to appropriate handler
        if (in_array($action, ['get_articles', 'get_article', 'create_article', 'update_article'])) {
            $handler = new ArticleHandler($responder);
            $handler->setUser($user);
            $handler->handle($action, $data);
        } elseif (in_array($action, ['get_categories'])) {
            $handler = new CategoryHandler($responder);
            $handler->setUser($user);
            $handler->handle($action, $data);
        } elseif (in_array($action, ['get_tags'])) {
            $handler = new TagHandler($responder);
            $handler->setUser($user);
            $handler->handle($action, $data);
        } else {
            $responder->badRequest('Invalid task: ' . $action);
        }
    }

}
