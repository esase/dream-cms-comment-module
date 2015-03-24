<?php
namespace Comment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class CommentCommenterName extends AbstractHelper
{
    /**
     * Get commenter name
     *
     * @param array $comment
     *   integer guest_id
     *   string name
     *   sting registred_nickname
     *   integer user_id
     * @return string
     */
    public function __invoke($comment)
    {
        $commenterName = !empty($comment['guest_id']) && empty($comment['user_id'])
            ? $comment['name']
            : (!empty($comment['registred_nickname']) ? $comment['registred_nickname'] : $this->getView()->translate('guest'));

        return $commenterName;
    }
}