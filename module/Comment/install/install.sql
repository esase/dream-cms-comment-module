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

-- system pages and widgets

INSERT INTO `page_widget` (`name`, `module`, `type`, `description`, `duplicate`, `forced_visibility`, `depend_page_id`) VALUES
('commentWidget', @moduleId, 'public', 'Comments', NULL, NULL, NULL);

-- module tables

CREATE TABLE `comment_list` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `comment` TEXT NOT NULL,
    `status` ENUM('approved','disapproved') NOT NULL,
    `page_id` SMALLINT(5) UNSIGNED NOT NULL,
    `slug` VARCHAR(255) DEFAULT NULL,
    `user_id` INT(10) UNSIGNED DEFAULT NULL,
    `left_key` INT(10) NOT NULL DEFAULT '0',
    `right_key` INT(10) NOT NULL DEFAULT '0',
    `level` INT(10) NOT NULL DEFAULT '0',
    `parent_id` INT(10) UNSIGNED DEFAULT NULL,
    `created` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `page_structure`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user_list`(`user_id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;