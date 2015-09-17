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
use User\Model\UserBase as BaseModel;
use User\Service\UserIdentity as UserIdentityService;

class CommentLastUserCommentsWidget extends AbstractCommentWidget
{
    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadScript()->
                appendFile($this->getView()->layoutAsset('last-comment.js', 'js', 'comment'));

        $this->getView()->layoutHeadLink()->
                appendStylesheet($this->getView()->layoutAsset('last-comment.css', 'css', 'comment'));
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (AclService::checkPermission('comment_view', false)) {
            // get the current user's info
            if (null != ($userInfo = 
                    UserIdentityService::getUserInfo($this->getSlug(), BaseModel::USER_INFO_BY_SLUG))) {

                // get last comments
                $comments = $this->getModel()->getLastComments($this->
                        getCurrentLanguage(), (int) $this->getWidgetSetting('comment_count'), $userInfo['user_id']);

                if (count($comments)) {
                    // increase ACL track
                    AclService::checkPermission('comment_view');

                    return $this->getView()->partial('comment/widget/user-last-comments-list', [
                        'visible_chars' => $this->getWidgetSetting('comment_visible_chars'),
                        'comments' => $comments
                    ]);
                }                
            }
        }

        return false;
    }
}