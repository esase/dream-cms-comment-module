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
     * Approve comment
     *
     * @apram integer $commentId
     * @return boolean
     */
    protected function approveComment($commentId)
    {
        if (AclService::checkPermission('comment_approve', false)) {
            if (null != ($commentInfo = $this->getModel()->
                    getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug()))) {

                    
                // approve comment
                if ($commentInfo['active'] == CommentNestedSet::COMMENT_STATUS_NOT_ACTIVE) {
                    if (true === ($result = $this->getModel()->getCommentModel()->approveComment($commentInfo))) {
                        // increase ACL track
                        AclService::checkPermission('comment_approve');
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get comment form
     *
     * @param boolean $allowApprove
     * @param boolean $validate
     * @return array|boolean
     */
    protected function getCommentForm($allowApprove, $validate = false)
    {
        if (AclService::checkPermission('comment_add', false)) {
            // get comment form settings
            $captchaEnabled = (int) $this->
                    getWidgetSetting('comment_form_captcha') && UserIdentityService::isGuest();

            $commentStatus = '';
            $commentInfo   = '';

            // get comment form
            $commentForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('Comment\Form\Comment')
                ->enableCaptcha($captchaEnabled)
                ->setGuestMode(UserIdentityService::isGuest());

            // validate the form
            if ($validate) {
                // fill form with received values
                $commentForm->getForm()->setData($this->getRequest()->getPost());

                // add a new comment
                if ($commentForm->getForm()->isValid()) {
                    $replyId  = $this->getRequest()->getQuery('widget_reply_id', null);
                    $userRole = UserIdentityService::getCurrentUserIdentity()['role'];

                    // get current page url
                    $pageUrl = $this->getView()->url('page', ['page_name' =>
                            $this->getView()->pageUrl(PageService::getCurrentPage()['slug']), 'slug' => $this->getPageSlug()], ['force_canonical' => true]);

                    $formData = $commentForm->getForm()->getData();

                    // get comment's status
                    $commentActive = (int) $this->getSetting('comments_auto_approve')
                            || $userRole ==  AclBaseModel::DEFAULT_ROLE_ADMIN || $allowApprove;

                    // collect basic data
                    $basicData = [
                        'active' => $commentActive ? CommentNestedSet::COMMENT_STATUS_ACTIVE : CommentNestedSet::COMMENT_STATUS_NOT_ACTIVE,
                        'comment' => $formData['comment'],
                        'name' => !empty($formData['name']) ? $formData['name'] : null,
                        'email' => !empty($formData['email']) ? $formData['email'] : null,
                        'user_id' => !UserIdentityService::isGuest()
                            ? UserIdentityService::getCurrentUserIdentity()['user_id']
                            : null
                    ];

                    $commentStatus = 'error';
                    $commentInfo = $this->getModel()->
                            getCommentModel()->addComment($pageUrl, $basicData, $this->pageId, $this->getPageSlug(), $replyId);

                    // return a status
                    if (is_array($commentInfo)) {
                        $commentStatus = $commentInfo['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE
                            ? 'success'
                            : 'disapproved';

                        // increase ACL track
                        AclService::checkPermission('comment_add');
                    }
                }
            }

            return [
                'comment' => $commentInfo && $commentStatus == 'success' ? $this->processComments([$commentInfo], true) : '',
                'status' => $commentStatus,
                'form' => $this->getView()->partial('comment/widget/_comment-form', [
                    'status' => $commentStatus,
                    'enable_captcha' => $captchaEnabled,
                    'guest_mode' => UserIdentityService::isGuest(),
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
    {//return false;
        // TODO:
        // 1.Show empty block
        // 2. Test with disapprove.+
        // 4. Test ACL AGAIN!
        // 5. Add notification about new messages.+
        // 6. TEST comments on different pages +
        // 7. Store ip +
        // 8 . Store email+
        // 9. Edit comments ????
        //10. DON't show access denied for absent comments (approve function)!

        if (AclService::checkPermission('comment_view', false)) {
            // is approve allowing
            $allowApprove = AclService::checkPermission('comment_approve', false);

            // process actions
            if (false !== ($action = $this->
                    getRequest()->getQuery('widget_action', false)) && $this->getRequest()->isXmlHttpRequest()) {

                switch ($action) {
                    case 'get_comments' :
                        // get the comment info
                        $lastCommentId = $this->getRequest()->getQuery('widget_last_comment', -1);
                        $commentInfo = $this->getModel()->
                                getCommentModel()->getCommentInfo($lastCommentId, $this->pageId, $this->getPageSlug());

                        if ($commentInfo) {
                            $leftComments = $this->getModel()->getCommentsCount($allowApprove, $this->
                                pageId, $this->getPageSlug(), $commentInfo[$this->getModel()->getCommentModel()->getRightKey()]);

                            return $this->getView()->json([
                                'show_paginator' => $leftComments - (int) $this->getWidgetSetting('comment_per_page') > 0,
                                'comments' => $this->getCommentsList($allowApprove, false,
                                        $commentInfo[$this->getModel()->getCommentModel()->getRightKey()], true)
                            ]);
                        }
                        break;

                    case 'add_comment'  :
                        // validate and add a new comment
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->getCommentForm($allowApprove, true));
                        }
                        break;

                    case 'approve_comment' :
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->
                                    approveComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;
                }
            }

            // get a comment form
            $commentForm = $this->getCommentForm($allowApprove);

            return $this->getView()->partial('comment/widget/comments-list', [
                'base_url' => $this->getWidgetConnectionUrl(),
                'comment_form' => false !== $commentForm ? $commentForm['form'] : null,
                'comments' => $this->getCommentsList($allowApprove),
                'show_paginator' => $this->getModel()->getCommentsCount($allowApprove,
                        $this->pageId, $this->getPageSlug()) > (int) $this->getWidgetSetting('comment_per_page')
            ]);
        }

        return false;
    }

    /**
     * Get comments list
     *
     * @param boolean $allowApprove
     * @param boolean $getTree
     * @param integer $lastRightKey
     * @param boolean $asArray
     * @param array $ownReplies
     * @return string|array
     */
    protected function getCommentsList($allowApprove, $getTree = true, $lastRightKey = null, $asArray = false, $ownReplies = null)
    {
        // get comments
        $commentsList = $this->getModel()->getComments($allowApprove, $this->pageId,
                $this->getPageSlug(), (int) $this->getWidgetSetting('comment_per_page'), $getTree, $lastRightKey, $ownReplies);

        // process comments
        if (null != ($commentsList = $this->processComments($commentsList, $asArray))) {
            // increase ACL track
            AclService::checkPermission('comment_view');
        }

        return $commentsList;
    }

    /**
     * Process comments
     *
     * @param array $comments
     * @return string|array
     */
    protected function processComments(array $comments, $asArray = false)
    {
        $processedComments = null;

        if (count($comments)) {
            // process comments
            foreach ($comments as $comment) {
                $content = $this->getView()->partial('comment/widget/_comment-item-start', [
                    'id' => $comment['id'],
                    'comment' => $comment['comment'],
                    'approved' => $comment['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE
                ]);

                // check for children
                if (!$asArray && !empty($comment['children'])) {
                    $content .= $this->processComments($comment['children']);
                }

                $content .= $this->getView()->partial('comment/widget/_comment-item-end');

                // collect proccessed comments
                !$asArray
                    ? $processedComments .= $content
                    : $processedComments[] = [
                        'id' => $comment['id'],
                        'parent_id' => $comment['parent_id'],
                        'comment' => $content
                    ];
            }
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