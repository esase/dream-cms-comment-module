<?php
namespace Comment\Model;

use Application\Service\ApplicationSetting as ApplicationSettingService;
use Application\Model\ApplicationAbstractBase;

class CommentBase extends ApplicationAbstractBase
{
    /**
     * Comment model instance
     * @var object  
     */
    protected $commentModel;

    /**
     * Get comment model
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