<?php
namespace MCPPlugin\Auth;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class TokenAuthenticator
{
    private $app;

    public function __construct($app = null)
    {
        $this->app = $app ?: Factory::getApplication();
    }

    public function authenticate()
    {
        $token = $this->getTokenFromRequest();
        
        if (empty($token)) {
            return null;
        }
        
        $user = $this->validateToken($token);
        
        if ($user && !$user->guest && $user->id > 0) {
            // Set user in session
            $session = Factory::getSession();
            $session->set('user', $user);
            
            return $user;
        }
        
        return null;
    }
    
    private function validateToken($token)
    {
        // Basic token validation - decode and extract user ID
        $decodedToken = base64_decode($token);
        if (!$decodedToken || !str_contains($decodedToken, ':')) {
            return null;
        }
        
        $tokenParts = explode(':', $decodedToken, 3);
        if (count($tokenParts) !== 3) {
            return null;
        }
        
        $userId = (int) $tokenParts[1];
        if ($userId <= 0) {
            return null;
        }
        
        // Check if user exists and has API token enabled
        $user = Factory::getUser($userId);
        if (!$user || $user->guest) {
            return null;
        }
        
        // Verify user has API token enabled
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('profile_value')
            ->from('#__user_profiles')
            ->where('user_id = ' . $userId)
            ->where('profile_key = ' . $db->quote('joomlatoken.enabled'));
        
        $db->setQuery($query);
        $tokenEnabled = $db->loadResult();
        
        if ($tokenEnabled != '1') {
            return null;
        }
        
        return $user;
    }

    private function getTokenFromRequest()
    {
        // Try X-Joomla-Token header first
        $token = $this->app->input->server->get('HTTP_X_JOOMLA_TOKEN', '');
        
        // If not found, try Authorization header (Bearer format)
        if (empty($token)) {
            $authHeader = $this->app->input->server->get('HTTP_AUTHORIZATION', '');
            if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        return $token;
    }

}
