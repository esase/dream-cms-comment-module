<?php
namespace Comment\View\Widget;

use Acl\Model\AclBase as AclBaseModel;
use Acl\Service\Acl as AclService;
use Comment\Model\CommentNestedSet;
use Page\Service\Page as PageService;
use Page\View\Widget\PageAbstractWidget;
use User\Service\UserIdentity as UserIdentityService;

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
            // get comment form settings
            $captchaEnabled = (int) $this->getWidgetSetting('comment_form_captcha');
            $commentStatus = '';

            // get comment form
            $commentForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('Comment\Form\Comment')
                ->enableCaptcha($captchaEnabled);

            // validate the form
            if ($validate) {
                // get a new comment settings
                $replyId = $this->getRequest()->getPost('reply_id', null);
                $pageSlug = !empty(PageService::getCurrentPage()['pages_provider']) ? $this->getSlug() : null;
                $replyComment = false;

                // get a reply comment info
                if ($replyId) {
                    $replyComment = $this->getModel()->getCommentInfo($replyId, $this->pageId, $pageSlug);
                }

                // the reply comment don't exsist
                if ($replyId && (!$replyComment
                        || $replyComment['active'] != CommentNestedSet::COMMENT_STATUS_ACTIVE)) {

                    $commentStatus = 'error';
                }
                else {
                    // fill form with received values
                    $commentForm->getForm()->setData($this->getRequest()->getPost());

                    // add a new comment
                    if ($commentForm->getForm()->isValid()) {
                        $commentActive = (int) $this->getSetting('comments_auto_approve')
                            || UserIdentityService::getCurrentUserIdentity()['role'] ==  AclBaseModel::DEFAULT_ROLE_ADMIN
                                    ? CommentNestedSet::COMMENT_STATUS_ACTIVE
                                    : CommentNestedSet::COMMENT_STATUS_NOT_ACTIVE;

                        // comment's data
                        $data = [
                            'comment' => $commentForm->getForm()->getData()['comment'],
                            'active' => $commentActive,
                            'page_id' => $this->pageId,
                            'slug' => $pageSlug,
                            'user_id' => !UserIdentityService::isGuest()
                                ? UserIdentityService::getCurrentUserIdentity()['user_id']
                                : null
                        ];

                        // add a new comment
                        if ($replyComment) {
                            $commentId = $this->getModel()->addComment($data, $this->
                                    pageId, $pageSlug, $replyComment['level'], $replyComment['left_key'], $replyComment['right_key']);
                        }
                        else {
                            $commentId = $this->getModel()->addComment($data, $this->pageId, $pageSlug);
                        }

                        $commentStatus = is_numeric($commentId)
                            ? ($commentActive ? 'success' : 'disapproved')
                            : 'error';
                    }
                }
            }

            return [
                'status' => $commentStatus,
                'form' => $this->getView()->partial('comment/widget/_comment-form', [
                    'status' => $commentStatus,
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
            // process comments actions
            if ($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
                $action = $this->getRequest()->getPost('action');

                switch ($action) {
                    case 'add_comment' :
                        // validate and add a new comment
                        return $this->getView()->json($this->getCommentForm(true));
                }
            }

            $commentForm = $this->getCommentForm();
            return $this->getView()->partial('comment/widget/comments-list', [
                'base_url' => $this->getWidgetConnectionUrl(),
                'comment_form' => false !== $commentForm ? $commentForm['form'] : null
            ]);
        }

        return false;
    }
}