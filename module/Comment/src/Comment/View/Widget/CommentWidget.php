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
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Comment\Model\CommentWidget');
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
                $replyComment = false;

                // get a reply comment info
                if ($replyId) {
                    $replyComment = $this->getModel()->
                            getCommentModel()->getCommentInfo($replyId, $this->pageId, $this->getPageSlug());
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
                            'slug' => $this->getPageSlug(),
                            'user_id' => !UserIdentityService::isGuest()
                                ? UserIdentityService::getCurrentUserIdentity()['user_id']
                                : null
                        ];

                        // add a new comment
                        if ($replyComment) {
                            $commentId = $this->getModel()->getCommentModel()->addComment($data, $this->
                                    pageId, $this->getPageSlug(), $replyComment['level'], $replyComment['left_key'], $replyComment['right_key']);
                        }
                        else {
                            $commentId = $this->getModel()->getCommentModel()->addComment($data, $this->pageId, $this->getPageSlug());
                        }

                        // return a status
                        if (is_numeric($commentId)) {
                            $commentStatus = $commentActive ? 'success' : 'disapproved';

                            // increase ACL track
                            AclService::checkPermission('comment_add');
                        }
                        else {
                            $commentStatus = 'error';
                        }
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

            // get a comment form
            $commentForm = $this->getCommentForm();

            return $this->getView()->partial('comment/widget/comments-list', [
                'base_url' => $this->getWidgetConnectionUrl(),
                'comment_form' => false !== $commentForm ? $commentForm['form'] : null,
                'comments' => $this->getCommentsList()
            ]);
        }

        return false;
    }

    /**
     * Get comments list
     *
     * @return string
     */
    protected function getCommentsList()
    {
        // get a pagination page number
        $pageParamName = 'page_' . $this->widgetConnectionId;
        $page = $this->getView()->applicationRoute()->getQueryParam($pageParamName , 1);

        if (null != ($commentsList = $this->processComments($this->
                getModel()->getCommentsTree($this->pageId, $this->getPageSlug(), $page)))) {

            // increase ACL track
            AclService::checkPermission('comment_view');
        }

        return $commentsList;
    }

    /**
     * Process comments
     *
     * @param array $comments
     * @return string
     */
    protected function processComments(array $comments)
    {
        $processedComments = null;

        if (count($comments)) {
            foreach ($comments as $comment) {
                if ($comment['active'] != CommentNestedSet::COMMENT_STATUS_ACTIVE) {
                    continue;
                }

                $processedComments .= $this->getView()->partial('comment/widget/_comment-item-start', [
                    'id' => $comment['id'],
                    'comment' => $comment['comment']
                ]);

                // check for children
                if (!empty($comment['children'])) {
                    $processedComments .= $this->processComments($comment['children']);
                }

                $processedComments .= $this->getView()->partial('comment/widget/_comment-item-end');
            }
            
            
            return $processedComments;
        }

        return $processedComments;
    }

    /**
     * Get page slug
     *
     * @return string|integer
     */
    protected function getPageSlug()
    {
        return !empty(PageService::getCurrentPage()['pages_provider']) ? $this->getSlug() : null;
    }
}