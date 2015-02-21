<?php
namespace Comment\View\Widget;

use Page\View\Widget\PageAbstractWidget;
use Acl\Service\Acl as AclService;

class CommentWidget extends PageAbstractWidget
{
    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadScript()->
                appendFile($this->getView()->layoutAsset('comment.js', 'js', 'comment'));
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        $viewComments = AclService::checkPermission('comment_view', false);
        $addComments  = AclService::checkPermission('comment_add', false);

        if ($viewComments || $addComments) {
            $commentForm = null;

            // get a comment form
            if ($addComments) {
                $commentForm = $this->getServiceLocator()
                    ->get('Application\Form\FormManager')
                    ->getInstance('Comment\Form\Comment');

                // validate the form
                if ($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
                    // fill form with received values
                    $commentForm->getForm()->setData($this->getRequest()->getPost());

                    if ($commentForm->getForm()->isValid()) {
                        return 'GREAt';
                        // SHOULD IT WORK WITH AJAX OINLY ?
                    }
                    else {
                        return 'no';
                    }
                }
            }

            #AclService::checkPermission('comment_view', true);
            return $this->getView()->partial('comment/widget/comment', [
                'url' => $this->getWidgetConnectionUrl(),
                'comment_form' => $commentForm ? $commentForm->getForm() : null
            ]);
        }

        return false;
    }
}