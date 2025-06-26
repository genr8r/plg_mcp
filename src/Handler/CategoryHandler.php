<?php
namespace MCPPlugin\Handler;

defined('_JEXEC') or die;

class CategoryHandler extends BaseHandler
{
    public function handle($action, $data)
    {
        switch ($action) {
            case 'get_categories':
                $this->getCategories($data);
                break;
            default:
                $this->responder->badRequest('Category action not implemented yet: ' . $action);
        }
    }

    private function getCategories($data)
    {
        if (!$this->checkPermission('core.manage', 'com_content')) {
            $this->responder->forbidden('Insufficient permissions to view categories');
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'title', 'alias', 'parent_id', 'level', 'published']))
            ->from($this->db->quoteName('#__categories'))
            ->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_content'))
            ->order($this->db->quoteName('lft') . ' ASC');

        $categories = $this->executePaginatedQuery($query, $data);
        $this->responder->success(['data' => $categories]);
    }
}
