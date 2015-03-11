<?php
namespace Comment\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class CommentWidget extends CommentBase
{
    /**
     * Get comments count
     *
     * @param boolean $allowApprove
     * @param integer $pageId
     * @param string $pageSlug
     * @param integer $lastRightKey
     * @return integer
     */
    public function getCommentsCount($allowApprove, $pageId, $pageSlug = null, $lastRightKey = null)
    {
        $select = $this->select();
        $select->from('comment_list')
            ->columns([
                'comments_count' => new Expression('COUNT(*)')
            ])
            ->where([
                'page_id' => $pageId,
                'slug' => $pageSlug
            ]);

        if (!$allowApprove) {
            $select->where([
                'hidden' => CommentNestedSet::COMMENT_STATUS_NOT_HIDDEN
            ]);
        }

        if ($lastRightKey) {
            $select->where->lessThan($this->getCommentModel()->getRightKey(), $lastRightKey);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return !empty($resultSet->current()->comments_count) ? $resultSet->current()->comments_count : 0;
    }

    /**
     * Get comments
     *
     * @param boolean $allowApprove
     * @param integer $pageId
     * @param integer $limit
     * @param string $pageSlug     
     * @param boolean $getTree
     * @param integer $lastRightKey
     * @return array
     */
    public function getComments($allowApprove, $pageId, $limit, $pageSlug = null, $getTree = true, $lastRightKey = null)
    {
        $select = $this->select();
        $select->from(['a' => 'comment_list'])
            ->columns([
                'id',
                'comment',
                'parent_id',
                'active',
                'user_id',
                'guest_id',
                'name'
            ])
            ->join(
                ['b' => 'user_list'],
                'a.user_id = b.user_id',
                [
                    'registred_nickname' => 'nick_name',
                    'registred_slug' => 'slug'
                ],
                'left'
            )
            ->where([
                'a.page_id' => $pageId,
                'a.slug' => $pageSlug
            ])
            ->limit($limit)
            ->order('a.' . $this->getCommentModel()->getRightKey() . ' desc');

        if (!$allowApprove) {
            $select->where([
                'a.hidden' => CommentNestedSet::COMMENT_STATUS_NOT_HIDDEN
            ]);
        }

        if ($lastRightKey) {
            $select->where->
                lessThan('a.' . $this->getCommentModel()->getRightKey(), $lastRightKey);
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