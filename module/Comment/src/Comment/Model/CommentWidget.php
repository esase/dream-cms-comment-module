<?php
namespace Comment\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class CommentWidget extends CommentBase
{
    /**
     * Get comments count
     *
     * @param integer $pageId
     * @param string $pageSlug
     * @param integer $lastRightKey
     * @param array $ownReplies
     * @return integer
     */
    public function getCommentsCount($pageId, $pageSlug = null, $lastRightKey = null, $ownReplies = null)
    {
        $select = $this->select();
        $select->from('comment_list')
            ->columns([
                'comments_count' => new Expression('COUNT(*)')
            ])
            ->where([
                'page_id' => $pageId,
                'slug' => $pageSlug,
                'hidden' => CommentNestedSet::COMMENT_STATUS_NOT_HIDDEN
            ]);

        if ($lastRightKey) {
            $select->where->lessThan($this->getCommentModel()->getRightKey(), $lastRightKey);
        }

        // skip own replies
        if ($ownReplies && is_array($ownReplies)) {
            $select->where->notIn('id', $ownReplies);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return !empty($resultSet->current()->comments_count) ? $resultSet->current()->comments_count : 0;
    }

    /**
     * Get comments
     *
     * @param integer $pageId
     * @param string $pageSlug
     * @param integer $limit
     * @param boolean $getTree
     * @param integer $lastRightKey
     * @param array $ownReplies
     * @return array
     */
    public function getComments($pageId, $pageSlug = null, $limit, $getTree = true, $lastRightKey = null, $ownReplies = null)
    {
        $select = $this->select();
        $select->from('comment_list')
            ->columns([
                'id',
                'comment',
                'parent_id'
            ])
            ->where([
                'page_id' => $pageId,
                'slug' => $pageSlug,
                'hidden' => CommentNestedSet::COMMENT_STATUS_NOT_HIDDEN
            ])
            ->limit($limit)
            ->order($this->getCommentModel()->getRightKey() . ' desc');

        if ($lastRightKey) {
            $select->where->lessThan($this->getCommentModel()->getRightKey(), $lastRightKey);
        }

        // skip own replies
        if ($ownReplies && is_array($ownReplies)) {
            $select->where->notIn('id', $ownReplies);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        if ($getTree && count($resultSet)) {
            $commentsTree = [];
            foreach ($resultSet as $comment) {
                $this->processCommentsTree($commentsTree, $comment);
            }

            return $commentsTree;
        }

        return $resultSet->toArray();
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