<?php
namespace Comment\View\Widget;

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
     * Get comment
     *
     * @param boolean $allowApprove
     * @param integer $commentId
     * @return array
     */
    protected function getComment($allowApprove, $commentId)
    {
        $comment = null;
        $message = $this->translate('Error occured, please try again later');

        if (null != ($commentInfo = $this->getModel()->
                getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug()))) {

            $allowView = $allowApprove || ($commentInfo['active'] ==
                    CommentNestedSet::COMMENT_STATUS_ACTIVE && $commentInfo['hidden'] == CommentNestedSet::COMMENT_STATUS_NOT_HIDDEN);
            

            if ($allowView) {
                $comment = [
                    'comment' => $commentInfo['comment'],
                    'name' => $commentInfo['name'],
                    'email' => $commentInfo['email'],
                ];

                $message = null;
            }
        }

        return [
            'comment' => $comment,
            'message' => $message
        ];
    }

    /**
     * Disapprove comment
     *
     * @apram integer $commentId
     * @return boolean|array
     */
    protected function disapproveComment($commentId)
    {
        if (AclService::checkPermission('comment_disapprove', false)) {
            if (null != ($commentInfo = $this->getModel()->
                    getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug()))) {

                // disapprove comment
                if ($commentInfo['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE) {
                    if (true === ($result = $this->getModel()->getCommentModel()->disapproveComment($commentInfo))) {
                        // increase ACL track
                        AclService::checkPermission('comment_disapprove');

                        return [
                            'status' => 'success',
                            'message' => ''
                        ];
                    }
                }
            }

            return [
                'status' => 'error',
                'message' => $this->translate('Error occured, please try again later')
            ];
        }

        return false;
    }

    /**
     * Delete comment
     *
     * @apram integer $commentId
     * @return boolean|array
     */
    protected function deleteComment($commentId)
    {
        $deleteComment = AclService::checkPermission('comment_delete', false);
        $deleteOwnComment = AclService::checkPermission('comment_delete_own', false);

        if ($deleteComment || $deleteOwnComment) {
            if (null != ($commentInfo = $this->getModel()->
                    getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug()))) {

                $userId = !UserIdentityService::isGuest()
                    ? UserIdentityService::getCurrentUserIdentity()['user_id']
                    : $this->getModel()->getCommentModel()->getGuestId();

                // check the comment's ownership
                $isOwner = $commentInfo['user_id'] == $userId || $commentInfo['guest_id'] == $userId;

                // delete comment
                if ($deleteComment || ($deleteOwnComment && $isOwner)) {
                    if (true === ($result = $this->getModel()->getCommentModel()->deleteComment($commentInfo))) {
                        // increase ACL track
                        if ($deleteOwnComment && $isOwner) {
                            AclService::checkPermission('comment_delete_own');
                        }

                        if ($deleteComment) {
                            AclService::checkPermission('comment_delete');
                        }

                        return [
                            'status' => 'success',
                            'message' => ''
                        ];
                    }
                }
            }

            return [
                'status' => 'error',
                'message' => $this->translate('Error occured, please try again later')
            ];
        }

        return false;
    }

    /**
     * Approve comment
     *
     * @apram integer $commentId
     * @return boolean|array
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

                        return [
                            'status' => 'success',
                            'message' => ''
                        ];
                    }
                }
            }

            return [
                'status' => 'error',
                'message' => $this->translate('Error occured, please try again later')
            ];
        }

        return false;
    }

    /**
     * Get add comment form
     *
     * @param boolean $allowApprove
     * @param boolean $validate
     * @return array|boolean
     */
    protected function getAddCommentForm($allowApprove, $validate = false)
    {
        if (AclService::checkPermission('comment_add', false)) {
            // get comment form settings
            $captchaEnabled = (int) $this->
                    getWidgetSetting('comment_form_captcha') && UserIdentityService::isGuest();

            $commentMessage = '';
            $commentStatus  = '';
            $commentInfo    = '';

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
                    $replyId  = $this->getRequest()->getQuery('widget_comment_id', null);

                    // get current page url
                    $pageUrl = $this->getView()->url('page', ['page_name' =>
                            $this->getView()->pageUrl(PageService::getCurrentPage()['slug']), 'slug' => $this->getPageSlug()], ['force_canonical' => true]);

                    $formData = $commentForm->getForm()->getData();

                    // get comment's status
                    $commentActive = $allowApprove || (int) $this->getSetting('comments_auto_approve');

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

                    $commentInfo = $this->getModel()->
                            getCommentModel()->addComment($pageUrl, $basicData, $this->pageId, $this->getPageSlug(), $replyId);

                    // return a status
                    if (is_array($commentInfo)) {
                        $commentStatus = 'success';

                        if ($commentInfo['active'] != CommentNestedSet::COMMENT_STATUS_ACTIVE) {
                            $commentMessage = $this->translate('Your comment will be available after approving');
                        }

                        // increase ACL track
                        AclService::checkPermission('comment_add');
                    }
                    else {
                        $commentStatus = 'error';
                        $commentMessage = $this->translate('Error occured, please try again later');
                    }
                }
            }

            return [
                'comment' => $commentInfo && $commentInfo['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE
                    ? $this->processComments([$commentInfo], true)
                    : '',
                'status'  => $commentStatus,
                'message' => $commentMessage,
                'form' => $this->getView()->partial('comment/widget/_comment-form', [
                    'enable_captcha' => $captchaEnabled,
                    'guest_mode' => UserIdentityService::isGuest(),
                    'comment_form' => $commentForm->getForm()
                ])
            ];
        }

        return false;
    }

    /**
     * Get edit comment form
     *
     * @param boolean $allowApprove
     * @param integer $commentId
     * @return array|boolean
     */
    protected function getEditCommentForm($allowApprove, $commentId)
    {
        $editComment = AclService::checkPermission('comment_edit', false);
        $editOwnComment = AclService::checkPermission('comment_edit_own', false);

        if ($editComment || $editOwnComment) {
            // get comment form settings
            $captchaEnabled = (int) $this->
                    getWidgetSetting('comment_form_captcha') && UserIdentityService::isGuest();

            $commentStatus = 'error';
            $commentMessage = $this->translate('Error occured, please try again later');
            $commentInfo    = '';

            // get comment form
            $commentForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('Comment\Form\Comment')
                ->enableCaptcha($captchaEnabled)
                ->setGuestMode(UserIdentityService::isGuest());

            // fill form with received values
            $commentForm->getForm()->setData($this->getRequest()->getPost());

            if (null != ($commentInfo = $this->getModel()->
                    getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug()))) {

                $userId = !UserIdentityService::isGuest()
                    ? UserIdentityService::getCurrentUserIdentity()['user_id']
                    : $this->getModel()->getCommentModel()->getGuestId();

                // check the comment's ownership
                $isOwner = $commentInfo['user_id'] == $userId || $commentInfo['guest_id'] == $userId;

                // edit the comment
                if ($editComment || ($editOwnComment && $isOwner)) {
                    // edit the comment
                    if ($commentForm->getForm()->isValid()) {
                        $formData = $commentForm->getForm()->getData();

                        // get the comment's status
                        $commentStatus = $allowApprove
                            ? $commentInfo['active'] // use the old value
                            : ((int) $this->getSetting('comments_auto_approve')
                                ? CommentNestedSet::COMMENT_STATUS_ACTIVE : CommentNestedSet::COMMENT_STATUS_NOT_ACTIVE);

                        // collect basic data
                        $basicData = [
                            'active' => $commentStatus,
                            'comment' => $formData['comment'],
                            'name' => !empty($formData['name']) ? $formData['name'] : $commentInfo['name'],
                            'email' => !empty($formData['email']) ? $formData['email'] : $commentInfo['email'] 
                        ];

                        $commentInfo = $this->getModel()->
                            getCommentModel()->editComment($commentInfo, $basicData, $this->pageId, $this->getPageSlug());

                        // return a status
                        if (is_array($commentInfo)) {
                            $commentStatus = 'success';
                            $commentMessage = '';

                            if (!$allowApprove && $commentInfo['active'] != CommentNestedSet::COMMENT_STATUS_ACTIVE) {
                                $commentMessage = $this->translate('Your comment will be available after approving');
                            }

                            // increase ACL track
                            if ($editOwnComment && $isOwner) {
                                AclService::checkPermission('comment_edit_own');
                            }

                            if ($editComment) {
                                AclService::checkPermission('comment_edit');
                            }
                        }
                    }
                    else {
                        $commentMessage = null;
                    }
                }
            }

            return [
                'comment' => $commentInfo && ($allowApprove || $commentInfo['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE)
                    ? $commentInfo['comment']
                    : '',
                'status'  => $commentStatus,
                'message' => $commentMessage,
                'form' => $this->getView()->partial('comment/widget/_comment-form', [
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
        // 1.Show empty block +
        // 2. Test with disapprove.+
        // 4. Test ACL AGAIN! +
        // 5. Add notification about new messages.+
        // 6. TEST comments on different pages +
        // 7. Store ip +
        // 8 . Store email+
        //10. DON't show access denied for absent comments (approve function)! +
        // 12. Add a confirm block for delete links +
        // 13. You need to get correct lastComment id after deleting +
        // 9. Edit comments +
        // 14. Show only small part of comment if it long +
        // 17. Messages stay innactive when you edit message and they become active +

        //11. Send reply on email ???
        //15 . Edit doesnt work if add comments disallowed
        // 16. Mark as a SPAM ???
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
                            return $this->getView()->json($this->getAddCommentForm($allowApprove, true));
                        }
                        break;

                    case 'get_comment'  :
                        return $this->getView()->
                                json($this->getComment($allowApprove, $this->getRequest()->getQuery('widget_comment_id', -1)));

                    case 'edit_comment'  :
                        // validate and edit the comment
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->
                                    getEditCommentForm($allowApprove, $this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;

                    case 'approve_comment' :
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->
                                    approveComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;

                    case 'disapprove_comment' :
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->
                                    disapproveComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;

                    case 'delete_comment' :
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->
                                    deleteComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;
                }
            }

            // get a comment form
            $commentForm = $this->getAddCommentForm($allowApprove);

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
            $userId = !UserIdentityService::isGuest()
                ? UserIdentityService::getCurrentUserIdentity()['user_id']
                : $this->getModel()->getCommentModel()->getGuestId();

            // process comments
            foreach ($comments as $comment) {
                $content = $this->getView()->partial('comment/widget/_comment-item-start', [
                    'id' => $comment['id'],
                    'parent_id' => $comment['parent_id'],
                    'comment' => $comment['comment'],
                    'approved' => $comment['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE,
                    'own_comment' => $userId == $comment['user_id'] || $userId == $comment['guest_id'],
                    'visible_chars' => (int) $this->getWidgetSetting('comment_visible_chars')
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