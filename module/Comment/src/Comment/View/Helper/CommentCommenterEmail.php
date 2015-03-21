<?php
namespace Comment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class CommentCommenterEmail extends AbstractHelper
{
    /**
     * Get commenter email
     *
     * @param array $comment
     *   integer guest_id
     *   string email
     *   sting registred_email
     *   integer user_id
     * @return string
     */
    public function __invoke($comment)
    {
        $commenterEmail = !empty($comment['guest_id']) && empty($comment['user_id'])
            ? $comment['email']
            : (!empty($comment['registred_email']) ? $comment['registred_email'] : null);

        return $commenterEmail;
    }
}