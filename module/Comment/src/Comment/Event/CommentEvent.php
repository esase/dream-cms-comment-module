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
     * Approve comment event
     */
    const APPROVE_COMMENT = 'comment_approve';

    /**
     * Disapprove comment event
     */
    const DISAPPROVE_COMMENT = 'comment_disapprove';

    /**
     * Delete comment event
     */
    const DELETE_COMMENT = 'comment_delete';

    /**
     * Edit comment event
     */
    const EDIT_COMMENT = 'comment_edit';

    /**
     * Add comment spam IP event
     */
    const ADD_COMMENT_SPAM_IP = 'comment_add_spam_ip';

    /**
     * Fire add comment spam IP event
     *
     * @param $commentId
     * @return void
     */
    public static function fireAddCommentSpamIpEvent($spamId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Comment spam ip added by guest'
            : 'Event - Comment spam ip added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$spamId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $spamId];

        self::fireEvent(self::ADD_COMMENT_SPAM_IP, 
                $spamId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit comment event
     *
     * @param $commentId
     * @return void
     */
    public static function fireEditCommentEvent($commentId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Comment edited by guest'
            : 'Event - Comment edited by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$commentId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $commentId];

        self::fireEvent(self::EDIT_COMMENT, 
                $commentId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete comment event
     *
     * @param $commentId
     * @return void
     */
    public static function fireDeleteCommentEvent($commentId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Comment deleted by guest'
            : 'Event - Comment deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$commentId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $commentId];

        self::fireEvent(self::DELETE_COMMENT, 
                $commentId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire disapprove comment event
     *
     * @param $commentId
     * @return void
     */
    public static function fireDisapproveCommentEvent($commentId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Comment disapproved by guest'
            : 'Event - Comment disapproved by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$commentId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $commentId];

        self::fireEvent(self::DISAPPROVE_COMMENT, 
                $commentId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire approve comment event
     *
     * @param $commentId
     * @return void
     */
    public static function fireApproveCommentEvent($commentId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Comment approved by guest'
            : 'Event - Comment approved by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$commentId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $commentId];

        self::fireEvent(self::APPROVE_COMMENT, 
                $commentId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

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
     *      string registred_language
     *      integer created
     *      integer active
     *      integer hidden    
     * @param array $parentComment
     *      integer id
     *      string comment
     *      string name
     *      string email
     *      string registred_nickname
     *      string registred_email
     *      string registred_language
     *      integer created
     *      integer active
     *      integer hidden
     * @return void
     */
    public static function fireAddCommentEvent($pageUrl, array $commentInfo, $parentComment = null)
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

        $serviceLocator = ServiceLocatorService::getServiceLocator();

        // send an email notification about add the new comment
        if (SettingService::getSetting('comment_added_send')) {
            $defaultLocalization = LocalizationService::getDefaultLocalization()['language'];

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

        // send a email notification about the new reply
        if ($parentComment && SettingService::getSetting('comment_reply_send')) {
            // don't send reply notifications if comments owners are equal
            if ($commentInfo['user_id'] != $parentComment['user_id'] 
                    || $commentInfo['email'] != $parentComment['email']) {

                // get notification language
                $notificationLanguage = $parentComment['registred_language']
                    ? $parentComment['registred_language'] // we should use the user's language
                    : LocalizationService::getDefaultLocalization()['language'];

                 EmailNotificationUtility::sendNotification(($parentComment['registred_email'] ? $parentComment['registred_email'] : $parentComment['email']),
                    SettingService::getSetting('comment_reply_title', $notificationLanguage),
                    SettingService::getSetting('comment_reply_message', $notificationLanguage), [
                        'find' => [
                            'PosterName',
                            'PosterEmail',
                            'Comment',
                            'ReplyUrl',
                            'ReplyId',
                            'Reply',
                            'Date'
                        ],
                        'replace' => [
                            (!empty($commentInfo['registred_nickname']) ? $commentInfo['registred_nickname'] : $commentInfo['name']),
                            (!empty($commentInfo['registred_email']) ? $commentInfo['registred_email'] : $commentInfo['email']),
                            $parentComment['comment'],
                            $pageUrl,
                            $commentInfo['id'],
                            $commentInfo['comment'],
                            $serviceLocator->get('viewHelperManager')->
                                get('applicationDate')->__invoke($commentInfo['created'], [], $notificationLanguage) 
                        ]
                    ]);
            }
        }
    }
}