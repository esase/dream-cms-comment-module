<?php
namespace Comment\Model;

use Application\Model\ApplicationAbstractBase;

class CommentBase extends ApplicationAbstractBase
{
    /**
     * Comment model instance
     * @var object  
     */
    protected $commentModel;

    /**
     * Get comment model
     */
    public function getCommentModel()
    {
        if (!$this->commentModel) {
            $this->commentModel = $this->serviceLocator->get('Comment\Model\CommentNestedSet');
        }

        return $this->commentModel;
    }
}