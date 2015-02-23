<?php
namespace Comment\Model;

use Application\Utility\ApplicationPagination as PaginationUtility;
use Application\Service\ApplicationSetting as SettingService;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Paginator\Paginator;

class CommentWidget extends CommentBase
{
    /**
     * Get comments tree
     *
     * @param integer $pageId
     * @param string $pageSlug
     * @param integer $page
     * @param integer $perPage
     * @return array
     */
    public function getCommentsTree($pageId, $pageSlug = null, $page = 1, $perPage = 0)
    {
        $commentsTree = [];

        $select = $this->select();
        $select->from('comment_list')
            ->columns([
                'id',
                'comment',
                'active',
                'parent_id'
            ])
            ->order($this->getCommentModel()->getLeftKey());

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        if ($paginator->count()) {
            foreach ($paginator as $comment) {
                $this->processCommentsTree($commentsTree, (array) $comment);
            }
        }

        return $commentsTree;
    }

    /**
     * Process comments tree
     *
     * @param array $comments
     * @param array $currentComment
     * @return void
     */
    protected function processCommentsTree(array &$comments, $currentComment)
    {
        if (empty($currentComment['parent_id'])) {
            $comments[] = $currentComment;
            return;
        }

        // searching for a parent
        foreach ($comments as $index => &$commentOptions) {
            if ($currentComment['parent_id'] == $commentOptions['id']) {
                $comments[$index]['children'][] = $currentComment;
                return;
            }

            // checking for children
            if (!empty($commentOptions['children'])) {
                $this->processCommentsTree($commentOptions['children'], $currentComment);
            }
        }
    }
}