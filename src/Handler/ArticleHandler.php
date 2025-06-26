<?php
namespace MCPPlugin\Handler;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Component\Content\Administrator\Model\ArticleModel;
use MCPPlugin\Validator\InputValidator;

class ArticleHandler extends BaseHandler
{
    public function handle($action, $data)
    {
        switch ($action) {
            case 'get_articles':
                $this->getArticles($data);
                break;
            case 'get_article':
                $this->getArticle($data);
                break;
            case 'create_article':
                $this->createArticle($data);
                break;
            case 'update_article':
                $this->updateArticle($data);
                break;
            default:
                $this->responder->badRequest('Invalid article action: ' . $action);
        }
    }

    private function getArticles($data)
    {
        if (!$this->checkPermission('core.manage', 'com_content')) {
            $this->responder->forbidden('Insufficient permissions to view articles');
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'title', 'alias', 'catid', 'state', 'created']))
            ->from($this->db->quoteName('#__content'));

        if (isset($data['catid'])) {
            $catid = InputValidator::validateInt($data['catid']);
            if ($catid > 0) {
                $query->where($this->db->quoteName('catid') . ' = ' . $catid);
            }
        }

        if (isset($data['state'])) {
            $state = InputValidator::validateState($data['state']);
            if ($state !== null) {
                $query->where($this->db->quoteName('state') . ' = ' . $state);
            }
        }

        if (!$this->isAdmin()) {
            $authorisedCategories = Access::getAuthorisedCategories($this->user->id, 'com_content');
            if (!empty($authorisedCategories)) {
                $query->where($this->db->quoteName('catid') . ' IN (' . implode(',', $authorisedCategories) . ')');
            } else {
                $this->responder->success(['data' => []]);
                return;
            }
        }

        $validOrderFields = ['id', 'title', 'created', 'modified', 'state', 'catid'];
        $this->applyCommonFilters($query, $data, $validOrderFields);
        $articles = $this->executePaginatedQuery($query, $data);
        $this->responder->success(['data' => $articles]);
    }

    private function getArticle($data)
    {
        $this->validateRequired($data, ['article_id']);
        $articleId = InputValidator::validateInt($data['article_id']);
        
        if ($articleId <= 0) {
            $this->responder->badRequest('Invalid article ID');
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'title', 'alias', 'catid', 'state', 'created', 'introtext', 'fulltext']))
            ->from($this->db->quoteName('#__content'))
            ->where($this->db->quoteName('id') . ' = ' . $articleId);

        $article = $this->executeSingleQuery($query);
        if (!$article) {
            $this->responder->notFound('Article not found');
        }
        $this->responder->success(['data' => $article]);
    }

    private function createArticle($data)
    {
        $this->validateRequired($data, ['title', 'articletext', 'catid']);
        $categoryId = InputValidator::validateInt($data['catid']);
        
        if (!$this->checkPermission('core.create', 'com_content.category.' . $categoryId)) {
            $this->responder->forbidden('Insufficient permissions to create articles');
        }

        $articleData = [
            'title' => InputValidator::validateString($data['title'], '', 255),
            'alias' => ApplicationHelper::stringURLSafe($data['title']),
            'articletext' => InputValidator::sanitizeHtml($data['articletext']),
            'catid' => $categoryId,
            'state' => isset($data['published']) && $data['published'] ? 1 : 0,
            'language' => '*',
            'created_by' => $this->user->id,
        ];

        $articleModel = new ArticleModel();
        try {
            if (!$articleModel->save($articleData)) {
                $this->responder->serverError($articleModel->getError());
            }
            $this->responder->success(['message' => 'Article created successfully', 'id' => $articleModel->getItem()->id], 201);
        } catch (\Exception $e) {
            $this->responder->serverError('Failed to create article: ' . $e->getMessage());
        }
    }

    private function updateArticle($data)
    {
        $this->validateRequired($data, ['article_id']);
        $articleId = InputValidator::validateInt($data['article_id']);
        
        if (!$this->checkPermission('core.edit', 'com_content.article.' . $articleId)) {
            $this->responder->forbidden('Insufficient permissions to edit article');
        }

        $updateData = ['id' => $articleId];
        
        if (isset($data['title'])) {
            $updateData['title'] = InputValidator::validateString($data['title'], '', 255);
            $updateData['alias'] = ApplicationHelper::stringURLSafe($data['title']);
        }
        if (isset($data['articletext'])) {
            $updateData['articletext'] = InputValidator::sanitizeHtml($data['articletext']);
        }

        $needsStateChange = false;
        $newState = null;
        
        if (isset($data['state'])) {
            $validatedState = InputValidator::validateState($data['state']);
            if ($validatedState === null) {
                $this->responder->badRequest('Invalid state value');
            }
            if ($validatedState == -2) {
                if (!$this->checkPermission('core.delete', 'com_content.article.' . $articleId)) {
                    $this->responder->forbidden('Insufficient permissions to delete article');
                }
            } else {
                if (!$this->checkPermission('core.edit.state', 'com_content.article.' . $articleId)) {
                    $this->responder->forbidden('Insufficient permissions to change article state');
                }
            }
            $needsStateChange = true;
            $newState = $validatedState;
        }

        $articleModel = new ArticleModel();
        try {
            if (count($updateData) > 1) {
                if (!$articleModel->save($updateData)) {
                    $this->responder->serverError($articleModel->getError());
                }
            }
            if ($needsStateChange) {
                if (!$articleModel->publish([$articleId], $newState)) {
                    $this->responder->serverError('Failed to update article state');
                }
            }
            $this->responder->success(['message' => 'Article updated successfully']);
        } catch (\Exception $e) {
            $this->responder->serverError('Failed to update article: ' . $e->getMessage());
        }
    }
}
