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
     * Get last comments
     * 
     * @param string $language
     * @param integer $limit    
     * @param integer $userId
     * @return object Zend\Db\ResultSet\ResultSet
     */
    public function getLastComments($language, $limit, $userId = null)
    {
        $select = $this->select();
        $select->from(['a' => 'comment_list'])
            ->columns([
                'id',
                'comment',
                'created',
                'slug'
            ])
            ->join(
                ['b' => 'page_structure'],
                'a.page_id = b.id',
                [
                    'page_slug' => 'slug'
                ]
            )
            ->join(
                ['c' => 'comment_list'],
                'c.id = a.parent_id',
                [
                    'reply_id' => 'id',
                    'reply_guest_name' => 'name',
                    'reply_user_id' => 'user_id'
                ],
                'left'
            )
            ->join(
                ['d' => 'user_list'],
                'd.user_id = c.user_id',
                [
                    'reply_nickname' => 'nick_name',
                    'reply_slug' => 'slug',
                ],
                'left'
            )
            ->where([
                'a.language' => $language,
                'a.hidden' => CommentNestedSet::COMMENT_STATUS_NOT_HIDDEN
            ])
            ->limit($limit)
            ->order('a.created desc');

        if ($userId) {
            $select->where([
                'a.user_id' => $userId
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet;
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
                'name',
                'created',
                'level'                
            ])
            ->join(
                ['b' => 'user_list'],
                'a.user_id = b.user_id',
                [
                    'registred_nickname' => 'nick_name',
                    'registred_slug' => 'slug',
                    'registred_avatar' => 'avatar'
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