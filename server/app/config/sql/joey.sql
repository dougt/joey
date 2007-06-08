-- MySQL Code for Joey

SET FOREIGN_KEY_CHECKS = 0;

--
-- Table structure for table `cake_sessions`
--

DROP TABLE IF EXISTS `cake_sessions`;
CREATE TABLE `cake_sessions` (
  `id` varchar(255) NOT NULL default '',
  `data` text,
  `expires` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `contentsources`
--

DROP TABLE IF EXISTS `contentsources`;
CREATE TABLE `contentsources` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `file_id` int(11) unsigned NOT NULL,
  `source` text,
  `contentsourcetype_id` int(11) unsigned NOT NULL default '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `file_id` (`file_id`),
  KEY `contentsourcetype_id` (`contentsourcetype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `contentsourcetypes`
--

DROP TABLE IF EXISTS `contentsourcetypes`;
CREATE TABLE `contentsourcetypes` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `upload_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `size` int(11) unsigned NOT NULL default '0',
  `type` varchar(255) NOT NULL default '',
  `original_name` varchar(255),
  `original_type` varchar(255),
  `original_size` int(11) unsigned,
  `preview_name` varchar(255),
  `preview_type` varchar(255),
  `preview_size` int(11) unsigned,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `upload_id` (`upload_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `operators`
--

DROP TABLE IF EXISTS `operators`;
CREATE TABLE `operators` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `provider` varchar(255) NOT NULL default '',
  `emaildomain` varchar(255) NOT NULL default '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `phones`
--

DROP TABLE IF EXISTS `phones`;
CREATE TABLE `phones` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `locale` varchar(255),
  `jad_name` varchar(255),
  `jar_name` varchar(255),
  `screen_width` int(8) unsigned NOT NULL,
  `screen_height` int(8) unsigned NOT NULL,
  `screen_bitdepth` int(8) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `uploads`
--

DROP TABLE IF EXISTS `uploads`;
CREATE TABLE `uploads` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `referrer` varchar(255) NOT NULL default '',
  `deleted` datetime default NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `uploads_users`
--
DROP TABLE IF EXISTS `uploads_users`;
CREATE TABLE `uploads_users` (
  `upload_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `owner` int(1) NOT NULL default 0,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`upload_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `username` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `confirmationcode` varchar(255) default NULL,
  `phone_id` int(11) unsigned default NULL,
  `operator_id` int(11) unsigned default NULL,
  `phonenumber` varchar(255) NOT NULL default '',
  `notes` text,
  `disabled` int(1) NOT NULL default '0',
  `administrator` int(1) NOT NULL default '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `phone_id` (`phone_id`),
  KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- CONSTRAINTS

ALTER TABLE `contentsources`
  ADD CONSTRAINT `contentsources_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`),
  ADD CONSTRAINT `contentsources_ibfk_2` FOREIGN KEY (`contentsourcetype_id`) REFERENCES `contentsourcetypes` (`id`);

ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `uploads_users`
  ADD CONSTRAINT `uploads_users_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `uploads_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`phone_id`) REFERENCES `phones` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`);

SET FOREIGN_KEY_CHECKS = 1;

-- DEFAULT DATA
INSERT INTO `contentsourcetypes` VALUES (1,'application/atom+xml','2007-03-18 09:45:49','2007-03-18 09:45:49');
INSERT INTO `contentsourcetypes` VALUES (2,'rss-source/text','2007-03-18 09:45:49','2007-03-18 09:45:49');
INSERT INTO `contentsourcetypes` VALUES (3,'microsummary/xml','2007-03-18 09:45:49','2007-03-18 09:45:49');
INSERT INTO `contentsourcetypes` VALUES (4,'widget/joey','2007-03-18 09:45:49','2007-03-18 09:45:49');

INSERT INTO `operators` VALUES (1,'Not Sure', '','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (2,'AT&T', 'mobile.att.net','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (3,'Cellular One','mobile.celloneusa.com','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (4,'Cingular', 'mycingular.net','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (5,'Metro PCS', 'mymetropcs.com','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (6,'Nextel', 'page.nextel.com','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (7,'Orange','orange.net','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (8,'O2', 'o2.co.uk','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (9,'T-Mobile', 'tmomail.net','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (10,'Verizon PCS', 'vtext.com','2007-03-18 09:19:04','0000-00-00 00:00:00');
INSERT INTO `operators` VALUES (11,'Sprint PCS', 'messaging.sprintpcs.com','2007-03-18 09:19:04','0000-00-00 00:00:00');

INSERT INTO `phones` VALUES (1, 'Not Sure', 'en-us', '', '', 0, 0, 0, '2007-03-18 09:19:04','0000-00-00 00:00:00');
