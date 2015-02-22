<?php
namespace Comment\View\Widget;

use Page\View\Widget\PageAbstractWidget;
use Acl\Service\Acl as AclService;
use User\Service\UserIdentity as UserIdentityService;
use Acl\Model\AclBase as AclBaseModel;

class CommentWidget extends PageAbstractWidget
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()->get('Comment\Model\CommentNestedSet');
        }

        return $this->model;
    }

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
     * Get comment form
     *
     * @param boolean $validate
     * @return array|boolean
     */
    protected function getCommentForm($validate = false)
    {
        if (AclService::checkPermission('comment_add', false)) {
            $status = null;
            $captchaEnabled = (int) $this->getWidgetSetting('comment_form_captcha');
            $replyId = $this->getRequest()->getPost('reply_id'); // TODO: Check comment exsisting

            $commentForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('Comment\Form\Comment')
                ->enableCaptcha($captchaEnabled);

            // validate the form
            if ($validate) {
                // fill form with received values
                $commentForm->getForm()->setData($this->getRequest()->getPost());

                if ($commentForm->getForm()->isValid()) {
                    // get comment status
                    $approved = (int) $this->getSetting('comments_auto_approve') 
                            || UserIdentityService::getCurrentUserIdentity()['role'] ==  AclBaseModel::DEFAULT_ROLE_ADMIN ? true : false;

                    // add a new comment
                    $commentId = $this->getModel()->addComment(0, 1, 2);

                    $status = is_numeric($commentId)
                        ? ($approved ? 'success' : 'disapproved')
                        : 'error';
                }
            }

            return [
                'status' => $status,
                'form' => $this->getView()->partial('comment/widget/_comment-form', [
                    'status' => $status,
                    'enable_captcha' => $captchaEnabled,
                    'comment_form' => $commentForm->getForm()
                ])
            ];
        }

        return false;
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (AclService::checkPermission('comment_view', false)) {
            // process action
            if ($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
                $action = $this->getRequest()->getPost('action');

                switch ($action) {
                    case 'add_comment' :
                        return $this->getView()->json($this->getCommentForm(true));
                }
            }

            return $this->getView()->partial('comment/widget/comments-list', [
                'url' => $this->getWidgetConnectionUrl(),
                'comment_form' => $this->getCommentForm()['form']
            ]);
        }

        return false;
    }
}