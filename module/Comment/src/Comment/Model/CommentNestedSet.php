<?php
namespace Comment\Model;

use Application\Model\ApplicationAbstractNestedSet;
use Zend\Db\Sql\Select;

class CommentNestedSet extends ApplicationAbstractNestedSet 
{
    /**
     * Comment status active 
     */
    const COMMENT_STATUS_ACTIVE = 1;

    /**
     * Comment status not active 
     */
    const COMMENT_STATUS_NOT_ACTIVE = 0;

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
            $select->where([
                'page_id' => $pageId
            ]);

            if ($slug) {
                $select->where([
                    'slug' => $slug
                ]);
            }

            return $select;
        });
    }

    /**
     * Add comment
     *
     * @param array $data
     *      string comment
     *      string status
     *      integer page_id
     *      string slug
     *      integer user_id
     * @param integer $pageId
     * @param string $slug
     * @param integer $parentLevel
     * @param integer $parentLeftKey
     * @param integer $parentRightKey
     * @return integer|string
     */
    public function addComment($data, $pageId, $slug = null, $parentLevel = 0, $parentLeftKey = 0, $parentRightKey = 1)
    {
        // TODO: FIRE AN EVENT AND SEND NOTIFICATION ABOUT NEW COMMENT
        $filter = [
            'page_id' =>  $pageId,
            'slug' => $slug
        ];

        $data = array_merge($data, [
            'created' => time()
        ]);

        $lastRightNode = !$parentLevel
            ? $this->getLastNode($filter)
            : $this->getLastNode($filter, $parentLeftKey, $parentRightKey);

        // add a comment to the end 
        if ($lastRightNode) {
            return $this->insertNode($parentLevel, $lastRightNode, $data, $filter);
        }

        // add comment to the start
        return $this->insertNodeToStart($parentLevel, $parentLeftKey, $data, $filter);        
    }
}