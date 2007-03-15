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
  `upload_id` int(11) unsigned NOT NULL,
  `source` text,
  `contentsourcetype_id` int(11) unsigned NOT NULL default '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `upload_id` (`upload_id`),
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
  `preview` varchar(255) NOT NULL default '',
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
  `user_id` int(11) unsigned NOT NULL,
  `title` varchar(255) NOT NULL default '',
  `referrer` varchar(255) NOT NULL default '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`)
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
  `phone_id` int(11) unsigned NOT NULL default '0',
  `operator_id` int(11) unsigned NOT NULL default '0',
  `phonenumber` varchar(255) NOT NULL default '',
  `notes` text,
  `disabled` int(1) NOT NULL default '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `phone_id` (`phone_id`),
  KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- CONSTRAINTS

ALTER TABLE `contentsources`
  ADD CONSTRAINT `contentsources_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`),
  ADD CONSTRAINT `contentsources_ibfk_2` FOREIGN KEY (`contentsourcetype_id`) REFERENCES `contentsourcetypes` (`id`);

ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`);

ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`phone_id`) REFERENCES `phones` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`);





SET FOREIGN_KEY_CHECKS = 1;
