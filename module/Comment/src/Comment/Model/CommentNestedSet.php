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

use Comment\Event\CommentEvent;
use Application\Utility\ApplicationErrorLogger;
use Application\Model\ApplicationAbstractNestedSet;
use Zend\Db\Sql\Select;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Session\Container as SessionContainer;
use Zend\Math\Rand;
use Exception;

class CommentNestedSet extends ApplicationAbstractNestedSet 
{
    /**
     * Comment status active 
     */
    const COMMENT_STATUS_ACTIVE = 1;

    /**
     * Comment status not active 
     */
    const COMMENT_STATUS_NOT_ACTIVE = null;

    /**
     * Comment status hidden 
     */
    const COMMENT_STATUS_HIDDEN = 1;

    /**
     * Comment status not hidden
     */
    const COMMENT_STATUS_NOT_HIDDEN = null;

    /**
     * Comment guest id length
     */
    CONST COMMENT_GUEST_ID_LENGTH = 32;

    /**
     * Get guest id
     *
     * @return string
     */
    public function getGuestId()
    {
        $container = new SessionContainer('comment');

        // generate custom guest id
        if (empty($container->guestId)) {
            $container->guestId =
                    Rand::getString(self::COMMENT_GUEST_ID_LENGTH, 'abcdefghijklmnopqrstuvwxyz', true);
        }

        return $container->guestId;
    }

    /**
     * Delete comment
     * 
     * @param array $commentInfo
     *  integer id
     *  integer page_id
     *  integer left_key
     *  integer right_key
     *  string slug
     * @return string|boolean
     */
    public function deleteComment(array $commentInfo)
    {
        $filter = [
            'page_id' => $commentInfo['page_id'],
            'slug' => $commentInfo['slug']
        ];

        if (true === ($result = $this->
                deleteNode($commentInfo[$this->left], $commentInfo[$this->right], $filter))) {

            // fire the delete comment event
            CommentEvent::fireDeleteCommentEvent($commentInfo['id']);
        }

        return $result;
    }

    /**
     * Disapprove comment
     *
     * @param array $commentInfo
     *  integer id
     *  integer page_id
     *  integer left_key
     *  integer right_key
     *  string slug
     * @return boolean|string
     */
    public function disapproveComment(array $commentInfo)
    {
        try {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();

            $this->tableGateway->update([
                'active' => self::COMMENT_STATUS_NOT_ACTIVE,
                'hidden' => self::COMMENT_STATUS_HIDDEN
            ], [$this->nodeId => $commentInfo['id']]);

            $filter = [
                'page_id' => $commentInfo['page_id'],
                'slug' => $commentInfo['slug']
            ];

            // add the hidden flag for all siblings comments
            $result = $this->updateSiblingsNodes([
                'hidden' => self::COMMENT_STATUS_HIDDEN
            ], $commentInfo[$this->left], $commentInfo[$this->right], null, $filter, false);

            if (true !== $result) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();

                return $result;
            }

            $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the disapprove comment event
        CommentEvent::fireDisapproveCommentEvent($commentInfo['id']);

        return true;
    }

    /**
     * Approve comment
     *
     * @param array $commentInfo
     *  integer id
     *  integer page_id
     *  integer left_key
     *  integer right_key
     *  string slug
     * @return boolean|string
     */
    public function approveComment(array $commentInfo)
    {
        try {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();

            $this->tableGateway->update([
                'active' => self::COMMENT_STATUS_ACTIVE,
                'hidden' => self::COMMENT_STATUS_NOT_HIDDEN
            ], [$this->nodeId => $commentInfo['id']]);

            $filter = [
                'page_id' => $commentInfo['page_id'],
                'slug' => $commentInfo['slug']
            ];

            // skip the hidden flag for all siblings comments
            $result = $this->updateSiblingsNodes([
                'hidden' => self::COMMENT_STATUS_NOT_HIDDEN
            ], $commentInfo[$this->left], $commentInfo[$this->right], null, $filter, false);

            if (true !== $result) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();

                return $result;
            }

            // hide not active siblings
            while (true) {
                $siblingFilter = array_merge($filter, [
                    'active' => self::COMMENT_STATUS_NOT_ACTIVE,
                    'hidden' => self::COMMENT_STATUS_NOT_HIDDEN]
                );

                $sibling = $this->getSiblingsNodes($commentInfo[$this->
                        left], $commentInfo[$this->right], null, $siblingFilter , null, 1);

                if (false !== $sibling) {
                    $sibling = array_shift($sibling);

                    $this->tableGateway->update([
                        'hidden' => self::COMMENT_STATUS_HIDDEN
                    ], [$this->nodeId => $sibling['id']]);
    
                    $result = $this->updateSiblingsNodes([
                        'hidden' => self::COMMENT_STATUS_HIDDEN
                    ], $sibling[$this->left], $sibling[$this->right], null, $filter, false);
    
                    if (true !== $result) {
                        $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();

                        return $result;
                    }

                    continue;    
                }

                break;
            }

            $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the approve comment event
        CommentEvent::fireApproveCommentEvent($commentInfo['id']);

        return true;
    }

    /**
     * Get comment info
     *
     * @param integer $id
     * @param integer $pageId
     * @param string $slug
     * @param string $language
     * @return array|boolean
     */
    public function getCommentInfo($id, $pageId = null, $slug = null, $language = null)
    {
        return $this->getNodeInfo($id, null, function (Select $select) use ($pageId, $slug, $language) {            
            $select->join(
                ['b' => 'user_list'],
                'b.user_id = ' . $this->tableGateway->table . '.user_id', 
                [
                    'registered_nickname' => 'nick_name',
                    'registered_slug' => 'slug',
                    'registered_email' => 'email',
                    'registered_avatar' => 'avatar',
                    'registered_language' => 'language'
                ],
                'left'
            );

            if ($pageId) {
                $select->where([
                    $this->tableGateway->table . '.page_id' => $pageId
                ]);
            }

            if ($slug) {
                $select->where([
                    $this->tableGateway->table . '.slug' => $slug
                ]);
            }

            if ($language) {
                $select->where([
                    $this->tableGateway->table . '.language' => $language
                ]);
            }

            return $select;
        });
    }

    /**
     * Add comment
     *
     * @param string $language
     * @param integer $maxNestedLevel
     * @param string $pageUrl
     * @param array $basicData
     *      integer active
     *      string comment
     *      string name
     *      string email
     *      integer user_id
     * @param integer $pageId
     * @param string $slug
     * @param integer $replyId
     * @return array|string
     */
    public function addComment($language, $maxNestedLevel, $pageUrl, array $basicData, $pageId, $slug = null, $replyId = null)
    {
        $replyComment = false;

        // get a reply comment info
        if ($replyId) {
            $replyComment = $this->getCommentInfo($replyId, $pageId, $slug);

            if ($replyComment['level'] > $maxNestedLevel) {
                return;
            }
        }

        // the reply comment doesn't exist or not active
        if ($replyId && !$replyComment) {
            return;
        }

        $filter = [
            'page_id' =>  $pageId,
            'slug' => $slug
        ];

        $remote = new RemoteAddress;
        $remote->setUseProxy(true);

        $commentHidden = $basicData['active'] == self::COMMENT_STATUS_NOT_ACTIVE
                || ($replyComment && $replyComment['hidden'] == CommentNestedSet::COMMENT_STATUS_HIDDEN);

        $data = array_merge($basicData, [
            'hidden' => $commentHidden
                ? self::COMMENT_STATUS_HIDDEN
                : self::COMMENT_STATUS_NOT_HIDDEN,
            'page_id' => $pageId,
            'slug' => $slug,
            'ip' => inet_pton($remote->getIpAddress()),
            'guest_id' => empty($basicData['user_id']) ? $this->getGuestId() : null,
            'created' => time(),
            'language' => $language
        ]);

        $parentLevel   = $replyComment ? $replyComment['level']    : 0;
        $parentLeftKey = $replyComment ? $replyComment['left_key'] : 0;

        // add reply comments to the start
        if ($parentLevel) {
            $commentId = $this->insertNodeToStart($parentLevel, $parentLeftKey, $data, $filter); 
        }
        else {
            $lastRightNode = $this->getLastNode($filter);

            // add a comment to the end 
            $commentId = $lastRightNode
                ? $this->insertNode($parentLevel, $lastRightNode, $data, $filter)
                : $this->insertNodeToStart($parentLevel, $parentLeftKey, $data, $filter);
        }

        if (is_numeric($commentId)) {
            $commentInfo = $this->getCommentInfo($commentId,  $pageId, $slug);

            // fire the add comment event
            CommentEvent::fireAddCommentEvent($pageUrl, $commentInfo, $replyComment);

            return $commentInfo;
        }

        return $commentId;
    }

    /**
     * Edit comment
     *
     * @param array $commentInfo
     *  integer id
     *  integer page_id
     *  integer left_key
     *  integer right_key
     *  string slug
     * @param array $basicData
     *      integer active
     *      string comment
     *      string name optional
     *      string email optional
     * @return array|string
     */
    public function editComment($commentInfo, array $basicData)
    {
        try {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();

            $hideSiblings = false;
            if ($basicData['active'] != self::COMMENT_STATUS_ACTIVE) {
                $hideSiblings = true;
                $basicData = array_merge($basicData, [
                    'hidden' => self::COMMENT_STATUS_HIDDEN
                ]);
            }

            $this->tableGateway->update($basicData, [$this->nodeId => $commentInfo['id']]);

            // add the hidden flag for all siblings comments
            if ($hideSiblings) {
                $filter = [
                    'page_id' => $commentInfo['page_id'],
                    'slug' => $commentInfo['slug']
                ];

                $result = $this->updateSiblingsNodes([
                    'hidden' => self::COMMENT_STATUS_HIDDEN
                ], $commentInfo[$this->left], $commentInfo[$this->right], null, $filter, false);
    
                if (true !== $result) {
                    $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();

                    return $result;
                }
            }

            $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        $commentInfo = $this->getCommentInfo($commentInfo['id'],  $commentInfo['page_id'], $commentInfo['slug']);

        // fire the edit comment event
        CommentEvent::fireEditCommentEvent($commentInfo['id']);

        return $commentInfo;
    }
}