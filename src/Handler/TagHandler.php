<?php
namespace MCPPlugin\Handler;

defined('_JEXEC') or die;

class TagHandler extends BaseHandler
{
    public function handle($action, $data)
    {
        switch ($action) {
            case 'get_tags':
                $this->getTags($data);
                break;
            default:
                $this->responder->badRequest('Tag action not implemented yet: ' . $action);
        }
    }

    private function getTags($data)
    {
        if (!$this->checkPermission('core.manage', 'com_tags')) {
            $this->responder->forbidden('Insufficient permissions to view tags');
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'title', 'alias', 'published']))
            ->from($this->db->quoteName('#__tags'))
            ->where($this->db->quoteName('id') . ' > 1')
            ->order($this->db->quoteName('title') . ' ASC');

        $tags = $this->executePaginatedQuery($query, $data);
        $this->responder->success(['data' => $tags]);
    }
}
