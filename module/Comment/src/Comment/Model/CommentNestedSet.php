<?php
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
            $container->guestId = Rand::
                    getString(self::COMMENT_GUEST_ID_LENGTH, 'abcdefghijklmnopqrstuvwxyz', true);
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
     * @return array|boolean
     */
    public function getCommentInfo($id, $pageId, $slug = null)
    {
        return $this->getNodeInfo($id, null, function (Select $select) use ($pageId, $slug) {
            $select->join(
                ['b' => 'user_list'],
                'b.user_id = ' . $this->tableGateway->table . '.user_id', 
                [
                    'registred_nickname' => 'nick_name',
                    'registred_slug' => 'slug',
                    'registred_email' => 'email',
                    'registred_avatar' => 'avatar',
                    'registred_language' => 'language'
                ],
                'left'
            )->where([
                $this->tableGateway->table . '.page_id' => $pageId,
                $this->tableGateway->table . '.slug' => $slug
            ]);

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

        // the reply comment don't exsist or not active
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
     *      string name
     *      string email
     * @param integer $pageId
     * @param string $slug
     * @return array|string
     */
    public function editComment($commentInfo, array $basicData, $pageId, $slug = null)
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