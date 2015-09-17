<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getLastComments($language, $limit, $userId = null)
    {
        $select = $this->select();
        $select->from(['a' => 'comment_list'])
            ->columns([
                'id',
                'comment',
                'created',
                'slug',
                'guest_name' => 'name',
                'guest_id',
                'user_id'
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
                    'reply_guest_id' => 'guest_id',
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
            ->join(
                ['i' => 'user_list'],
                'a.user_id = i.user_id',
                [
                    'nick_name',
                    'user_slug' => 'slug',
                    'avatar'
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
                    'registered_nickname' => 'nick_name',
                    'registered_slug' => 'slug',
                    'registered_avatar' => 'avatar'
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