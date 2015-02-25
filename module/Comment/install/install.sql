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
('comment_add', 'ACL - Adding comments', @moduleId);
SET @addCommentResourceId = (SELECT LAST_INSERT_ID());

INSERT INTO `acl_resource_connection` (`role`, `resource`) VALUES
(3, @addCommentResourceId),
(2, @addCommentResourceId);

-- application settings

INSERT INTO `application_setting_category` (`name`, `module`) VALUES
('Main settings', @moduleId);
SET @settingsCategoryId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting` (`name`, `label`, `description`, `type`, `required`, `order`, `category`, `module`, `language_sensitive`, `values_provider`, `check`, `check_message`) VALUES
('comments_auto_approve', 'Comments auto approve', NULL, 'checkbox', NULL, 1, @settingsCategoryId, @moduleId, 1, NULL, NULL, NULL);
SET @settingId = (SELECT LAST_INSERT_ID());

INSERT INTO `application_setting_value` (`setting_id`, `value`, `language`) VALUES
(@settingId, '1', NULL);

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

-- module tables

CREATE TABLE `comment_list` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `comment` TEXT NOT NULL,
    `active` TINYINT(1) UNSIGNED NULL DEFAULT '1',
    `hidden` TINYINT(1) UNSIGNED NULL DEFAULT '1',
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `slug` VARCHAR(255) DEFAULT NULL,
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
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