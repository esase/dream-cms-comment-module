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
        //return false;
        if (AclService::checkPermission('comment_add', false)) {
            // get comment form settings
            $captchaEnabled = (int) $this->getWidgetSetting('comment_form_captcha');
            $commentStatus = '';
            $commentInfo   = '';

            // get comment form
            $commentForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('Comment\Form\Comment')
                ->enableCaptcha($captchaEnabled);

            // validate the form
            if ($validate) {
                // fill form with received values
                $commentForm->getForm()->setData($this->getRequest()->getPost());

                // add a new comment
                if ($commentForm->getForm()->isValid()) {
                    $replyId = $this->getRequest()->getQuery('reply_id', null);

                    // get the comment status
                    $commentActive = (int) $this->getSetting('comments_auto_approve')
                            || UserIdentityService::getCurrentUserIdentity()['role'] ==  AclBaseModel::DEFAULT_ROLE_ADMIN
                                ? CommentNestedSet::COMMENT_STATUS_ACTIVE
                                : CommentNestedSet::COMMENT_STATUS_NOT_ACTIVE;

                    $userId = !UserIdentityService::isGuest()
                        ? UserIdentityService::getCurrentUserIdentity()['user_id']
                        : null;
 
                    $commentStatus = 'error';
                    $comment   = $commentForm->getForm()->getData()['comment'];
                    $commentId = $this->getModel()->getCommentModel()->
                            addComment($commentActive, $comment, $this->pageId, $this->getPageSlug(), $userId, $replyId);

                    // return a status
                    if (is_numeric($commentId)) {
                        // get the comment info
                        $commentInfo = $this->getModel()->
                                getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug());

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
        // TODO: 1. Get a comment content after adding. 1.1 Show empty block 2. Test with disapprove. 3. Add System event. 4. Test ACL AGAIN!
        //return false;
        if (AclService::checkPermission('comment_view', false)) {
            // process actions
            if (false !== ($action = $this->
                    getRequest()->getQuery('action', false)) && $this->getRequest()->isXmlHttpRequest()) {

                switch ($action) {
                    case 'get_comments' :
                        // get the comment info
                        $ownReplies = $this->getRequest()->getPost('own_replies', null);
                        $lastCommentId = $this->getRequest()->getQuery('last_comment', -1);
                        $commentInfo = $this->getModel()->
                                getCommentModel()->getCommentInfo($lastCommentId, $this->pageId, $this->getPageSlug());

                        if ($commentInfo) {
                            $leftComments = $this->getModel()->getCommentsCount($this->pageId, $this->
                                    getPageSlug(), $commentInfo[$this->getModel()->getCommentModel()->getRightKey()], $ownReplies);

                            return $this->getView()->json([
                                'show_paginator' => $leftComments - (int) $this->getWidgetSetting('comment_per_page') > 0,
                                'comments' => $this->getCommentsList(false,
                                        $commentInfo[$this->getModel()->getCommentModel()->getRightKey()], true, $ownReplies)
                            ]);
                        }
                        break;

                    case 'add_comment'  :
                        // validate and add a new comment
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->getCommentForm(true));
                        }
                        break;
                }
            }

            // get a comment form
            $commentForm = $this->getCommentForm();

            return $this->getView()->partial('comment/widget/comments-list', [
                'base_url' => $this->getWidgetConnectionUrl(),
                'comment_form' => false !== $commentForm ? $commentForm['form'] : null,
                'comments' => $this->getCommentsList(),
                'show_paginator' => $this->getModel()->getCommentsCount($this->
                        pageId, $this->getPageSlug()) > (int) $this->getWidgetSetting('comment_per_page')
            ]);
        }

        return false;
    }

    /**
     * Get comments list
     *
     * @param boolean $getTree
     * @param integer $lastRightKey
     * @param boolean $asArray
     * @param array $ownReplies
     * @return string|array
     */
    protected function getCommentsList($getTree = true, $lastRightKey = null, $asArray = false, $ownReplies = null)
    {
        // get comments
        $commentsList = $this->getModel()->getComments($this->pageId, $this->
                getPageSlug(), (int) $this->getWidgetSetting('comment_per_page'), $getTree, $lastRightKey, $ownReplies);

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
                    'comment' => $comment['comment']
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