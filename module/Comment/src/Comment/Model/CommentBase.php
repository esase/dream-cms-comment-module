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
namespace Comment\Model;

use Application\Service\ApplicationSetting as ApplicationSettingService;
use Application\Model\ApplicationAbstractBase;

class CommentBase extends ApplicationAbstractBase
{
    /**
     * Comment model instance
     *
     * @var \Comment\Model\CommentNestedSet
     */
    protected $commentModel;

    /**
     * Get comment model
     *
     * @return \Comment\Model\CommentNestedSet
     */
    public function getCommentModel()
    {
        if (!$this->commentModel) {
            $this->commentModel = $this->serviceLocator->get('Comment\Model\CommentNestedSet');
        }

        return $this->commentModel;
    }

    /**
     * Is spam IP
     *
     * @param string $ip
     * @return boolean
     */
    public function isSpamIp($ip)
    {
        return in_array($ip, $this->getSpamIps());
    }

    /**
     * Get spam Ips
     * 
     * @return array
     */
    protected function getSpamIps()
    {
        $spamIps = trim(ApplicationSettingService::getSetting('comment_spam_ips'));

        return $spamIps ? explode(',', $spamIps) : [];
    }

    /**
     * Spam comment
     *
     * @param string $ip
     * @return boolean|string
     */
    public function spamComment($ip)
    {
        $currentIpsList = $this->getSpamIps();

        if (!in_array($ip, $currentIpsList)) {
            $moduleName = 'comment';

            // get all module's settings
            $settings = $this->serviceLocator
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\ApplicationSettingAdministration');

            $settingsList = $settings->getSettingsList($moduleName, $this->getCurrentLanguage());
            $newIpsList   = implode(',', array_merge($currentIpsList, [$ip]));

            $settings->saveSettings($settingsList, [
                'comment_spam_ips' => $newIpsList
            ], $this->getCurrentLanguage(), $moduleName);
        }

        return true;
    }
}