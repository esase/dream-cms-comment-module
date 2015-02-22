<?php
namespace Comment\Model;

use Application\Model\ApplicationAbstractNestedSet;

class CommentNestedSet extends ApplicationAbstractNestedSet 
{
    /**
     * Add page
     *
     * @param integer $level
     * @param integer $leftKey
     * @param integer $rightKey
     * @return integer|string
     */
    public function addComment($level, $leftKey, $rightKey)
    {
        return $this->insertNodeToEnd($level, $rightKey, [
            'comment' => 'aaaa',
            'status' => 'approved',
            'page_id' => '1',
            'slug' => null,
            'user_id' => null,
            'created' => time()
        ]);
    }
}