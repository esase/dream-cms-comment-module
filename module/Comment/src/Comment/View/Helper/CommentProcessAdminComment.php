<?php
namespace Comment\View\Helper;

use Application\Service\ApplicationSetting as ApplicationSettingService;
use Zend\View\Helper\AbstractHelper;

class CommentProcessAdminComment extends AbstractHelper
{
    /**
     * Process comment
     *
     * @param array $comment
     *      string comment
     *      integer|string slug
     *      integer id
     *      string page_slug
     * @return string
     */
    public function __invoke($comment)
    {
        $maxLength = (int) ApplicationSettingService::getSetting('comment_length_in_admin');

        $processedComment = mb_strlen($comment['comment']) > $maxLength
            ? mb_substr($comment['comment'], 0, $maxLength) . '...'
            : $comment['comment'];

        return $this->getView()->partial('comment/administration-partial/comment-link', [
            'id' => $comment['id'],
            'comment' => $processedComment,
            'slug' => $comment['slug'],
            'comment_id' => $comment['id'],
            'page_slug' => $comment['page_slug']
        ]);
    }
}