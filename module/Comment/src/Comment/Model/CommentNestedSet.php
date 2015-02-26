<?php
namespace Comment\Model;

use Comment\Event\CommentEvent;
use Application\Model\ApplicationAbstractNestedSet;
use Zend\Db\Sql\Select;
use Zend\Http\PhpEnvironment\RemoteAddress;

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
                    'registred_email' => 'email'
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
    public function addComment($pageUrl, array $basicData, $pageId, $slug = null, $replyId = null)
    {
        $replyComment = false;

        // get a reply comment info
        if ($replyId) {
            $replyComment = $this->getCommentInfo($replyId, $pageId, $slug);
        }

        // the reply comment don't exsist or not active
        if ($replyId && (!$replyComment
                || $replyComment['active'] == CommentNestedSet::COMMENT_STATUS_NOT_ACTIVE
                || $replyComment['hidden'] == CommentNestedSet::COMMENT_STATUS_HIDDEN)) {

            return;
        }

        $filter = [
            'page_id' =>  $pageId,
            'slug' => $slug
        ];

        $remote = new RemoteAddress;
        $remote->setUseProxy(true);

        $data = array_merge($basicData, [
            'hidden' => $basicData['active'] == self::COMMENT_STATUS_NOT_ACTIVE
                ? self::COMMENT_STATUS_HIDDEN
                : self::COMMENT_STATUS_NOT_HIDDEN,
            'page_id' => $pageId,
            'slug' => $slug,
            'ip' => ip2long($remote->getIpAddress()),
            'created' => time()
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
            CommentEvent::fireAddCommentEvent($pageUrl, $commentInfo);
            return $commentInfo;
        }

        return $commentId;
    }
}