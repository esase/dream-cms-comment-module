<?php
namespace Comment\Event;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;
use Application\Service\ApplicationSetting as SettingService;
use User\Service\UserIdentity as UserIdentityService;
use Application\Event\ApplicationAbstractEvent;
use Localization\Service\Localization as LocalizationService;

class CommentEvent extends ApplicationAbstractEvent
{
    /**
     * Add comment event
     */
    const ADD_COMMENT = 'comment_add';

    /**
     * Fire add comment event
     *
     * @param string $pageUrl
     * @param array $commentInfo
     *      integer id
     *      string comment
     *      string name
     *      string email
     *      string registred_nickname
     *      string registred_email
     *      integer created
     *      integer active
     *      integer hidden
     * @return void
     */
    public static function fireAddCommentEvent($pageUrl, array $commentInfo)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Comment added by guest'
            : 'Event - Comment added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$commentInfo['id']]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $commentInfo['id']];

        self::fireEvent(self::ADD_COMMENT, 
                $commentInfo['id'], UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);

        // send an email notification about add the new comment
        if (SettingService::getSetting('comment_added_send')) {
            $defaultLocalization = LocalizationService::getDefaultLocalization()['language'];
            $serviceLocator = ServiceLocatorService::getServiceLocator();

            EmailNotificationUtility::sendNotification(SettingService::getSetting('application_site_email'),
                SettingService::getSetting('comment_added_title', $defaultLocalization),
                SettingService::getSetting('comment_added_message', $defaultLocalization), [
                    'find' => [
                        'PosterName',
                        'PosterEmail',
                        'CommentUrl',
                        'CommentId',
                        'Comment',
                        'Date'
                    ],
                    'replace' => [
                        (!empty($commentInfo['registred_nickname']) ? $commentInfo['registred_nickname'] : $commentInfo['name']),
                        (!empty($commentInfo['registred_email']) ? $commentInfo['registred_email'] : $commentInfo['email']),
                        $pageUrl,
                        $commentInfo['id'],
                        $commentInfo['comment'],
                        $serviceLocator->get('viewHelperManager')->
                                get('applicationDate')->__invoke($commentInfo['created'], [], $defaultLocalization)                        
                    ]
                ]);
        }
    }
}