<?php
namespace Comment\View\Widget;

use Page\View\Widget\PageAbstractWidget;

class CommentWidget extends PageAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        return $this->getView()->partial('comment/widget/comment', [
        ]);
    }
}