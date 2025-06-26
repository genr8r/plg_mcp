<?php
namespace MCPPlugin\Handler;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use MCPPlugin\Response\JsonResponder;
use MCPPlugin\Validator\InputValidator;

abstract class BaseHandler
{
    protected $responder;
    protected $user;
    protected $db;

    public function __construct(JsonResponder $responder)
    {
        $this->responder = $responder;
        $this->db = Factory::getDbo();
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    abstract public function handle($action, $data);

    protected function checkPermission($action, $asset = 'com_content')
    {
        return $this->user->authorise($action, $asset);
    }

    protected function validateRequired($data, $requiredFields)
    {
        $missing = InputValidator::validateRequired($data, $requiredFields);
        if (!empty($missing)) {
            $this->responder->badRequest('Missing required fields: ' . implode(', ', $missing));
        }
    }

    protected function applyCommonFilters($query, $data, $validOrderFields = [])
    {
        if (!empty($validOrderFields)) {
            $orderBy = InputValidator::validateOrderField(
                $data['order_by'] ?? '', 
                $validOrderFields, 
                $validOrderFields[0] ?? 'id'
            );
            $orderDirection = InputValidator::validateOrderDirection($data['order_direction'] ?? 'ASC');
            $query->order($this->db->quoteName($orderBy) . ' ' . $orderDirection);
        }
        return $query;
    }

    protected function executePaginatedQuery($query, $data)
    {
        $pagination = InputValidator::validatePagination($data);
        if ($pagination['limit'] > 0) {
            $this->db->setQuery($query, $pagination['offset'], $pagination['limit']);
        } else {
            $this->db->setQuery($query);
        }
        try {
            return $this->db->loadAssocList();
        } catch (\Exception $e) {
            $this->responder->serverError('Database error: ' . $e->getMessage());
        }
    }

    protected function executeSingleQuery($query)
    {
        $this->db->setQuery($query);
        try {
            return $this->db->loadAssoc();
        } catch (\Exception $e) {
            $this->responder->serverError('Database error: ' . $e->getMessage());
        }
    }

    protected function buildSearchCondition($searchTerm, $fields)
    {
        if (empty($searchTerm) || empty($fields)) {
            return '';
        }
        $search = $this->db->quote('%' . $this->db->escape($searchTerm, true) . '%');
        $conditions = [];
        foreach ($fields as $field) {
            $conditions[] = $this->db->quoteName($field) . ' LIKE ' . $search;
        }
        return '(' . implode(' OR ', $conditions) . ')';
    }

    protected function isAdmin()
    {
        return $this->checkPermission('core.admin');
    }
}
