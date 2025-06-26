<?php
namespace MCPPlugin\Response;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class JsonResponder
{
    private $app;

    public function __construct($app = null)
    {
        $this->app = $app ?: Factory::getApplication();
    }

    public function success($data = [], $statusCode = 200)
    {
        $this->sendResponse($data, $statusCode);
    }

    public function badRequest($message)
    {
        $this->sendResponse(['error' => $message], 400);
    }

    public function unauthorized($message = 'Unauthorized')
    {
        $this->sendResponse(['error' => $message], 401);
    }

    public function forbidden($message = 'Forbidden')
    {
        $this->sendResponse(['error' => $message], 403);
    }

    public function notFound($message = 'Not Found')
    {
        $this->sendResponse(['error' => $message], 404);
    }

    public function serverError($message)
    {
        $this->sendResponse(['error' => $message], 500);
    }

    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        $this->app->setHeader('Content-Type', 'application/json');
        
        if (is_array($data)) {
            $data['timestamp'] = date('c');
        }
        
        $this->app->setBody(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->app->sendHeaders();
        echo $this->app->getBody();
        $this->app->close();
    }
}
