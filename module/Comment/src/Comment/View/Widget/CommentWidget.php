<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Comment\View\Widget;

use Acl\Service\Acl as AclService;
use Comment\Model\CommentNestedSet;
use Page\Service\Page as PageService;
use User\Service\UserIdentity as UserIdentityService;
use Application\Utility\ApplicationCsrf as ApplicationCsrfUtility;

class CommentWidget extends AbstractCommentWidget
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

        $this->getView()->layoutHeadLink()->
                appendStylesheet($this->getView()->layoutAsset('main.css', 'css', 'comment'));
    }

    /**
     * Disapprove comment
     *
     * @param integer $commentId
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
                'message' => $this->translate('Error occurred, please try again later')
            ];
        }

        return false;
    }

    /**
     * Delete comment
     *
     * @param integer $commentId
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
                'message' => $this->translate('Error occurred, please try again later')
            ];
        }

        return false;
    }

    /**
     * Spam comment
     *
     * @param integer $commentId
     * @return boolean|array
     */
    protected function spamComment($commentId)
    {
        if (AclService::checkPermission('comment_spam', false)) {
            if (null != ($commentInfo = $this->getModel()->
                    getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug()))) {

                // mark as spam
                if (true === ($result = $this->getModel()->spamComment(inet_ntop($commentInfo['ip'])))) {
                    // increase ACL track
                    AclService::checkPermission('comment_spam');

                    // delete the comment
                    if (true === ($result = $this->getModel()->getCommentModel()->deleteComment($commentInfo))) {
                        AclService::checkPermission('comment_delete');

                        return [
                            'status' => 'success',
                            'message' => ''
                        ];
                    }
                }
            }

            return [
                'status' => 'error',
                'message' => $this->translate('Error occurred, please try again later')
            ];
        }

        return false;
    }

    /**
     * Approve comment
     *
     * @param integer $commentId
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
                'message' => $this->translate('Error occurred, please try again later')
            ];
        }

        return false;
    }

    /**
     * Get add comment form
     *
     * @param boolean $allowApprove
     * @return array|boolean
     */
    protected function getAddCommentForm($allowApprove)
    {
        if (AclService::checkPermission('comment_add', false)) {
            // get comment form settings
            $captchaEnabled = (int) $this->
                    getWidgetSetting('comment_form_captcha') && UserIdentityService::isGuest();

            $commentMessage = '';
            $commentStatus  = '';
            $commentInfo    = '';

            // get comment form
            $commentForm = $this->initCommentForm($captchaEnabled, $allowApprove);

            // validate the form
            $commentForm->getForm()->setData($this->getRequest()->getPost());

            // add a new comment
            if ($commentForm->getForm()->isValid()) {
                $replyId  = $this->getRequest()->getQuery('widget_comment_id', null);

                // get current page url
                $pageUrl = $this->getView()->url('page', ['page_name' =>
                        $this->getView()->pageUrl(PageService::getCurrentPage()['slug']), 'slug' => $this->getPageSlug()], ['force_canonical' => true]);

                $formData = $commentForm->getForm()->getData();

                // get comment's status
                $commentActive = $allowApprove || (int) $this->getSetting('comment_auto_approve');
                $maxNestedLevel = (int) $this->getWidgetSetting('comment_max_nested_level');

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

                $commentInfo = $this->getModel()->getCommentModel()->addComment($this->
                        getCurrentLanguage(), $maxNestedLevel, $pageUrl, $basicData, $this->pageId, $this->getPageSlug(), $replyId);

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
                    $commentMessage = $this->translate('Error occurred, please try again later');
                }
            }

            return [
                'comment' => $commentInfo && $commentInfo['active']
                        == CommentNestedSet::COMMENT_STATUS_ACTIVE ? $this->processComments([$commentInfo], true) : '',

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
            $commentMessage = $this->translate('Error occurred, please try again later');
            $commentInfo    = '';

            // get comment form
            $commentForm = $this->initCommentForm($captchaEnabled, $allowApprove);

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
                            : ((int) $this->getSetting('comment_auto_approve')
                                ? CommentNestedSet::COMMENT_STATUS_ACTIVE : CommentNestedSet::COMMENT_STATUS_NOT_ACTIVE);

                        // collect basic data
                        $basicData = [
                            'active' => $commentStatus,
                            'comment' => $formData['comment'],
                            'name' => !empty($formData['name']) ? $formData['name'] : $commentInfo['name'],
                            'email' => !empty($formData['email']) ? $formData['email'] : $commentInfo['email'] 
                        ];

                        $commentInfo = $this->getModel()->getCommentModel()->editComment($commentInfo, $basicData);

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
                'guest_name' => empty($commentInfo['user_id']) ? $commentInfo['name'] : null,
                'comment' => $commentInfo && ($allowApprove || $commentInfo['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE) 
                        ? $this->getView()->commentProcessComment($commentInfo['comment']) : '',

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
     * Init comment form
     *
     * @param boolean $captchaEnabled
     * @param boolean $allowApprove
     * @param integer $commentId
     * @return boolean|object Comment\Form\Comment
     */
    protected function initCommentForm($captchaEnabled, $allowApprove, $commentId = null)
    {
        // get comment form
        $commentForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Comment\Form\Comment')
            ->enableCaptcha($captchaEnabled)
            ->setGuestMode(UserIdentityService::isGuest())
            ->setModel($this->getModel());

        // get a comment info
        if ($commentId) {
            $commentInfo = $this->getModel()->
                    getCommentModel()->getCommentInfo($commentId, $this->pageId, $this->getPageSlug());

            if (!$commentInfo || (!$allowApprove && ($commentInfo['active'] !=
                    CommentNestedSet::COMMENT_STATUS_ACTIVE || $commentInfo['hidden'] != CommentNestedSet::COMMENT_STATUS_NOT_HIDDEN))) {

                return false;
            }

            // fill form with default values
            $commentForm->getForm()->setData($commentInfo);
        }

        return $commentForm;
    }

    /**
     * Get comment form
     *
     * @param boolean $allowApprove
     * @param integer $commentId
     * @return array
     */
    protected function getCommentForm($allowApprove = false, $commentId = null)
    {
        $captchaEnabled = (int) $this->
                getWidgetSetting('comment_form_captcha') && UserIdentityService::isGuest();

        // init a form
        if (false !== ($form = $this->
                initCommentForm($captchaEnabled, $allowApprove, $commentId))) {

            return [
                'form' => $this->getView()->partial('comment/widget/_comment-form', [
                    'enable_captcha' => $captchaEnabled,
                    'guest_mode' => UserIdentityService::isGuest(),
                    'comment_form' => $form->getForm()
                ])
            ];
        }

        return [
            'message' => $this->translate('Error occurred, please try again later')
        ];
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
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
                            return $this->getView()->json($this->getAddCommentForm($allowApprove));
                        }
                        break;

                    case 'get_form'  :
                        return $this->getView()->
                                json($this->getCommentForm($allowApprove, $this->getRequest()->getQuery('widget_comment_id', null)));

                    case 'edit_comment'  :
                        // validate and edit the comment
                        if ($this->getRequest()->isPost()) {
                            return $this->getView()->json($this->
                                    getEditCommentForm($allowApprove, $this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;

                    case 'approve_comment' :
                        if ($this->getRequest()->isPost() &&
                                ApplicationCsrfUtility::isTokenValid($this->getRequest()->getPost('csrf'))) {

                            return $this->getView()->json($this->
                                    approveComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;

                    case 'disapprove_comment' :
                        if ($this->getRequest()->isPost() &&
                                ApplicationCsrfUtility::isTokenValid($this->getRequest()->getPost('csrf'))) {

                            return $this->getView()->json($this->
                                    disapproveComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;

                    case 'delete_comment' :
                        if ($this->getRequest()->isPost() &&
                                ApplicationCsrfUtility::isTokenValid($this->getRequest()->getPost('csrf'))) {

                            return $this->getView()->json($this->
                                    deleteComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;

                    case 'spam_comment' :
                        if ($this->getRequest()->isPost() &&
                                ApplicationCsrfUtility::isTokenValid($this->getRequest()->getPost('csrf'))) {

                            return $this->getView()->json($this->
                                    spamComment($this->getRequest()->getQuery('widget_comment_id', -1)));
                        }
                        break;
                }
            }

            return $this->getView()->partial('comment/widget/comments-list', [
                'csrf_token' => ApplicationCsrfUtility::getToken(),
                'base_url' => $this->getWidgetConnectionUrl(),
                'comment_form' => AclService::checkPermission('comment_add', false) ? $this->getCommentForm()['form'] : null,
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
                (int) $this->getWidgetSetting('comment_per_page'), $this->getPageSlug(), $getTree, $lastRightKey, $ownReplies);

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
     * @param boolean $asArray
     * @return string|array
     */
    protected function processComments(array $comments, $asArray = false)
    {
        $processedComments = null;

        if (count($comments)) {
            $userId = !UserIdentityService::isGuest()
                ? UserIdentityService::getCurrentUserIdentity()['user_id']
                : $this->getModel()->getCommentModel()->getGuestId();

            $maxRepliesNestedLevel = (int) $this->getWidgetSetting('comment_max_nested_level');
            $showUsersThumbs = (int) $this->getWidgetSetting('comment_show_thumbs');

            // process comments
            foreach ($comments as $comment) {
                $content = $this->getView()->partial('comment/widget/_comment-item-start', [
                    'id' => $comment['id'],
                    'parent_id' => $comment['parent_id'],
                    'comment' => $comment['comment'],
                    'approved' => $comment['active'] == CommentNestedSet::COMMENT_STATUS_ACTIVE,
                    'own_comment' => $userId == $comment['user_id'] || $userId == $comment['guest_id'],
                    'visible_chars' => (int) $this->getWidgetSetting('comment_visible_chars'),
                    'registered_nickname' => $comment['registered_nickname'],
                    'guest_id' => $comment['guest_id'],
                    'name' => $comment['name'],
                    'user_id' => $comment['user_id'],
                    'user_slug' => $comment['registered_slug'],
                    'user_avatar' => $comment['registered_avatar'],
                    'created' => $comment['created'],
                    'show_reply' => $comment['level'] <= $maxRepliesNestedLevel,
                    'show_thumbs' => $showUsersThumbs
                ]);

                // check for children
                if (!$asArray && !empty($comment['children'])) {
                    $content .= $this->processComments($comment['children']);
                }

                $content .= $this->getView()->partial('comment/widget/_comment-item-end');

                // collect processed comments
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