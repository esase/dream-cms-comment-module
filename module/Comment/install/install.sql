SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';

SET @moduleId = __module_id__;

-- acl resources

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('comment_view', 'ACL - Viewing comments', @moduleId);
SET @viewCommentResourceId = (SELECT LAST_INSERT_ID());

INSERT INTO `acl_resource_connection` (`role`, `resource`) VALUES
(3, @viewCommentResourceId),
(2, @viewCommentResourceId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('comment_approve', 'ACL - Approving comments', @moduleId),
('comment_disapprove', 'ACL - Disapproving comments', @moduleId),
('comment_delete', 'ACL - Deleting comments', @moduleId),
('comment_edit', 'ACL - Editing comments', @moduleId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('comment_add', 'ACL - Adding comments', @moduleId);
SET @addCommentResourceId = (SELECT LAST_INSERT_ID());

INSERT INTO `acl_resource_connection` (`role`, `resource`) VALUES
(3, @addCommentResourceId),
(2, @addCommentResourceId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('comment_edit_own', 'ACL - Editing own comments', @moduleId);
SET @editCommentResourceId = (SELECT LAST_INSERT_ID());

INSERT INTO `acl_resource_connection` (`role`, `resource`) VALUES
(3, @editCommentResourceId),
(2, @editCommentResourceId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('comment_delete_own', 'ACL - Deleting own comments', @moduleId);
SET @deleteCommentResourceId = (SELECT LAST_INSERT_ID());

INSERT INTO `acl_resource_connection` (`role`, `resource`) VALUES
(3, @deleteCommentResourceId),
(2, @deleteCommentResourceId);

-- application events

INSERT INTO `application_event` (`name`, `module`, `description`) VALUES
('comment_add', @moduleId, 'Event - Adding comments'),
('comment_approve', @moduleId, 'Event - Approving comments'),
('comment_disapprove', @moduleId, 'Event - Disapproving comments'),
('comment_delete', @moduleId, 'Event - Deleting comments'),
('comment_edit', @moduleId, 'Event - Editing comments');

-- application settings

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('Main settings', @moduleId);
SET @settingsCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comments_auto_approve', 'Comments auto approve', NULL, 'checkbox', NULL, 1, @settingsCategoryId, @moduleId, NULL, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, '1', NULL);

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('Email notifications', @moduleId);
SET @settingsCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comment_added_send', 'Send notifications about new comments', NULL, 'checkbox', NULL, 1, @settingsCategoryId, @moduleId, NULL, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, '1', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comment_added_title', 'Comment added title', 'A comment add notification', 'notification_title', 1, 2, @settingsCategoryId, @moduleId, 1, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, 'A new comment on the page', NULL),
(@settingId, 'Новый комментарий на странице', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comment_added_message', 'Comment added message', NULL, 'notification_message', 1, 3, @settingsCategoryId, @moduleId, 1, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, '<p><b>__PosterName__ (__PosterEmail__):</b></p><p><a href="__CommentUrl__#comment-__CommentId__">__Comment__</a></p><p>__Date__</p>', NULL),
(@settingId, '<p><b>__PosterName__ (__PosterEmail__):</b></p><p><a href="__CommentUrl__#comment-__CommentId__">__Comment__</a></p><p>__Date__</p>', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comment_reply_send', 'Send notifications about new replies', NULL, 'checkbox', NULL, 4, @settingsCategoryId, @moduleId, NULL, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, '1', NULL);

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comment_reply_title', 'Comment reply title', 'A comment reply notification', 'notification_title', 1, 5, @settingsCategoryId, @moduleId, 1, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, 'You have a new reply', NULL),
(@settingId, 'У вас есть новый ответ', 'ru');

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comment_reply_message', 'Comment reply message', NULL, 'notification_message', 1, 6, @settingsCategoryId, @moduleId, 1, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, '<p><b>__PosterName__ (__PosterEmail__) replied to you:</b></p><p><q>__Comment__</q></p><p><a href="__ReplyUrl__#comment-__ReplyId__">__Reply__</a></p><p>__Date__</p>', NULL),
(@settingId, '<p><b>__PosterName__ (__PosterEmail__) ответил(а) вам:</b></p><p><q>__Comment__</q></p><p><a href="__ReplyUrl__#comment-__ReplyId__">__Reply__</a></p><p>__Date__</p>', 'ru');

-- system pages and widgets

INSERT INTO `page_widget` (`name`, `module`, `type`, `description`, `duplicate`, `forced_visibility`, `depend_page_id`) VALUES
('commentWidget', @moduleId, 'public', 'Comments', NULL, NULL, NULL);
SET @widgetId = (SELECT LAST_INSERT_ID());

INSERT INTO `page_widget_setting_category` (`name`, `module`) VALUES
('Display options', @moduleId);
SET @displaySettingCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `page_widget_setting` (`name`, `widget`, `label`, `type`, `required`, `order`, `category`, `description`) VALUES
('comment_form_captcha', @widgetId, 'Show captcha', 'checkbox', NULL, 1, @displaySettingCategoryId, NULL);

INSERT INTO `page_widget_setting` (`name`, `widget`, `label`, `type`, `required`, `order`, `category`, `description`, `check`,  `check_message`, `values_provider`) VALUES
('comment_per_page', @widgetId, 'Count of comments per page', 'integer', 1, 2, @displaySettingCategoryId, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0', NULL);
SET @widgetSettingId = (SELECT LAST_INSERT_ID());

INSERT INTO `page_widget_setting_default_value` (`setting_id`, `value`, `language`) VALUES
(@widgetSettingId, '20', NULL);

INSERT INTO `page_widget_setting` (`name`, `widget`, `label`, `type`, `required`, `order`, `category`, `description`, `check`,  `check_message`, `values_provider`) VALUES
('comment_visible_chars', @widgetId, 'Visible count of chars in comments', 'integer', 1, 3, @displaySettingCategoryId, NULL, 'return intval(''__value__'') > 0;', 'Value should be greater than 0', NULL);
SET @widgetSettingId = (SELECT LAST_INSERT_ID());

INSERT INTO `page_widget_setting_default_value` (`setting_id`, `value`, `language`) VALUES
(@widgetSettingId, '450', NULL);

INSERT INTO `page_system_widget_hidden` (`page_id`, `widget_id`) VALUES
(2,  @widgetId),
(3,  @widgetId),
(4,  @widgetId),
(5,  @widgetId),
(6,  @widgetId),
(7,  @widgetId),
(8,  @widgetId),
(9,  @widgetId),
(11, @widgetId),
(12, @widgetId),
(13, @widgetId);

-- module tables

CREATE TABLE `comment_list` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `comment` TEXT NOT NULL,
    `ip` INT(10) UNSIGNED NOT NULL,
    `name` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(50) DEFAULT NULL,
    `active` TINYINT(1) UNSIGNED NULL DEFAULT '1',
    `hidden` TINYINT(1) UNSIGNED NULL DEFAULT '1',
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `slug` VARCHAR(255) DEFAULT NULL,
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `guest_id` CHAR(32) DEFAULT NULL,
    `left_key` INT(10) NOT NULL DEFAULT '0',
    `right_key` INT(10) NOT NULL DEFAULT '0',
    `level` INT(10) NOT NULL DEFAULT '0',
    `parent_id` INT(10) UNSIGNED DEFAULT NULL,
    `created` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `node` (`left_key`, `right_key`, `page_id`, `slug`, `active`, `level`),
    KEY `comment` (`page_id`, `slug`, `hidden`, `right_key`),
    FOREIGN KEY (`page_id`) REFERENCES `page_structure`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user_list`(`user_id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;