<?php
namespace Comment\View\Widget;

use Acl\Service\Acl as AclService;

class CommentLastCommentsWidget extends AbstractCommentWidget
{
    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadScript()->
                appendFile($this->getView()->layoutAsset('last-comment.js', 'js', 'comment'));

        $this->getView()->layoutHeadLink()->
                appendStylesheet($this->getView()->layoutAsset('last-comment.css', 'css', 'comment'));
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (AclService::checkPermission('comment_view', false)) {
            // get last comments
            $comments = $this->getModel()->getLastComments($this->
                    getCurrentLanguage(), (int) $this->getWidgetSetting('comment_count'));

            if (count($comments)) {
                // increase ACL track
                AclService::checkPermission('comment_view');

                return $this->getView()->partial('comment/widget/last-comments-list', [
                    'visible_chars' => $this->getWidgetSetting('comment_visible_chars'),
                    'comments' => $comments
                ]);
            }
        }

        return false;
    }
}