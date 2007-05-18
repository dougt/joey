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
  `phone_id` int(11) unsigned default NULL,
  `operator_id` int(11) unsigned default NULL,
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
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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

INSERT INTO `phones` VALUES (1, 'Alcatel - One Touch 735i', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (2, 'Alcatel - One Touch 756', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (3, 'BenQ - P30', '208', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (4, 'BlackBerry - 5810', '160', '160', '1', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (5, 'BlackBerry - 7100t', '240', '260', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (6, 'BlackBerry - 7290', '240', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (7, 'BlackBerry - 7520', '240', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (8, 'BlackBerry - 8100', '240', '260', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (9, 'BlackBerry - 8700', '320', '240', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (10, 'BlackBerry - 8800', '320', '240', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (11, 'BlackBerry - Pearl', '240', '260', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (12, 'Casio - C452CA', '120', '133', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (13, 'DoJa - os15', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (14, 'DoJa - os25', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (15, 'Generic - DefaultColorPhone', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (16, 'Generic - DefaultGrayPhone', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (17, 'Generic - DotNetCF1.1', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (18, 'Generic - Java', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (19, 'Generic - JtwiCldc11', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (20, 'Generic - MediaControlSkin', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (21, 'Generic - Midp2Cldc11', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (22, 'Generic - Midp2Cldc11Pointer', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (23, 'Generic - MppPhone', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (24, 'Generic - PlainMidp1', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (25, 'Generic - PlainMidp2', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (26, 'Generic - PlainMidp2Cldc11', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (27, 'Generic - QwertyDevice', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (28, 'Generic - jsr185', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (29, 'Generic - jtwi', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (30, 'Generic - midp1', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (31, 'Generic - midp2', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (32, 'Generic - multi', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (33, 'Generic - wmapi20', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (34, 'HTC - Himalaya', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (35, 'Hitachi - C3001H', '120', '162', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (36, 'Kyocera - C3002K', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (37, 'LG - C-nain 2000', '120', '133', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (38, 'LG - LX5350', '120', '198', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (39, 'LG - U8138', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (40, 'Mio - 8390', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (41, 'Mitsubishi - J-D05', '', '', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (42, 'Motorola - A1000', '208', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (43, 'Motorola - A1200', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (44, 'Motorola - A388', '240', '320', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (45, 'Motorola - A630', '220', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (46, 'Motorola - A728', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (47, 'Motorola - A760', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (48, 'Motorola - A780', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (49, 'Motorola - A830', '176', '220', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (50, 'Motorola - A845', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (51, 'Motorola - A910', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (52, 'Motorola - A920', '208', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (53, 'Motorola - A925', '208', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (54, 'Motorola - C370', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (55, 'Motorola - C380', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (56, 'Motorola - C450', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (57, 'Motorola - C550', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (58, 'Motorola - C650', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (59, 'Motorola - C975', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (60, 'Motorola - C980', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (61, 'Motorola - E1', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (62, 'Motorola - E1000', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (63, 'Motorola - E2', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (64, 'Motorola - E380', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (65, 'Motorola - E398', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (66, 'Motorola - E398Emulator', '176', '204', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (67, 'Motorola - E6', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (68, 'Motorola - E680', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (69, 'Motorola - E680i', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (70, 'Motorola - E770', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (71, 'Motorola - FOMA_M1000', '208', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (72, 'Motorola - I870', '176', '188', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (73, 'Motorola - K1', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (74, 'Motorola - L2', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (75, 'Motorola - L6', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (76, 'Motorola - L6i', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (77, 'Motorola - L7', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (78, 'Motorola - MPx200', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (79, 'Motorola - Motokrzr_K1', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (80, 'Motorola - Motorazr-maxx-V6', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (81, 'Motorola - Motorazr_V3-CLDC1.1', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (82, 'Motorola - Motorazr_V3e', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (83, 'Motorola - Motorazr_V3i', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (84, 'Motorola - Motorazr_V3t', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (85, 'Motorola - Motorazr_V3x', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (86, 'Motorola - Motorazr_V3xx', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (87, 'Motorola - Motorizr_Z3', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (88, 'Motorola - Motorokr_E1', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (89, 'Motorola - Motorokr_E2', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (90, 'Motorola - Motorokr_E6', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (91, 'Motorola - Motoslvr_L7', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (92, 'Motorola - PEBL-U6', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (93, 'Motorola - Q', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (94, 'Motorola - SLVR', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (95, 'Motorola - T280i', '128', '100', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (96, 'Motorola - T720(CDMA)', '120', '160', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (97, 'Motorola - T720(GSM)', '120', '160', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (98, 'Motorola - T720i', '120', '160', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (99, 'Motorola - T725', '120', '160', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (100, 'Motorola - V1050', '240', '320', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (101, 'Motorola - V1100', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (102, 'Motorola - V180', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (103, 'Motorola - V195', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (104, 'Motorola - V197', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (105, 'Motorola - V220', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (106, 'Motorola - V3', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (107, 'Motorola - V3-CLDC1.0', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (108, 'Motorola - V3-CLDC1.1', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (109, 'Motorola - V300', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (110, 'Motorola - V303', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (111, 'Motorola - V360', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (112, 'Motorola - V365', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (113, 'Motorola - V3e', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (114, 'Motorola - V3i', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (115, 'Motorola - V3t', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (116, 'Motorola - V3x', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (117, 'Motorola - V3xx', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (118, 'Motorola - V400', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (119, 'Motorola - V500', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (120, 'Motorola - V525', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (121, 'Motorola - V550', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (122, 'Motorola - V551', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (123, 'Motorola - V6', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (124, 'Motorola - V600', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (125, 'Motorola - V620', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (126, 'Motorola - V635', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (127, 'Motorola - V8', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (128, 'Motorola - V80', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (129, 'Motorola - V980', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (130, 'Motorola - Z3', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (131, 'Motorola - i50sx', '111', '100', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (132, 'Motorola - i55sr', '111', '100', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (133, 'Motorola - i730', '130', '130', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (134, 'Motorola - i80s', '119', '64', '1', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (135, 'Motorola - i85s', '111', '100', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (136, 'Motorola - i88s', '111', '100', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (137, 'Motorola - i90c', '111', '100', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (138, 'Motorola - i95cl', '120', '160', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (139, 'Nokia - 2355', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (140, 'Nokia - 2610', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (141, 'Nokia - 2626', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (142, 'Nokia - 2855', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (143, 'Nokia - 2865', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (144, 'Nokia - 2865i', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (145, 'Nokia - 3100', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (146, 'Nokia - 3108', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (147, 'Nokia - 3120', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (148, 'Nokia - 3152', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (149, 'Nokia - 3155', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (150, 'Nokia - 3155i', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (151, 'Nokia - 3200', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (152, 'Nokia - 3220', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (153, 'Nokia - 3230', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (154, 'Nokia - 3300', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (155, 'Nokia - 3410', '96', '65', '1', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (156, 'Nokia - 3510i', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (157, 'Nokia - 3520', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (158, 'Nokia - 3530', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (159, 'Nokia - 3560', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (160, 'Nokia - 3587', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (161, 'Nokia - 3590', '96', '65', '1', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (162, 'Nokia - 3595', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (163, 'Nokia - 3600', '176', '208', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (164, 'Nokia - 3620', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (165, 'Nokia - 3650', '176', '208', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (166, 'Nokia - 3660', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (167, 'Nokia - 5100', '128', '160', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (168, 'Nokia - 5140', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (169, 'Nokia - 5140i', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (170, 'Nokia - 5200', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (171, 'Nokia - 5300', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (172, 'Nokia - 5500', '208', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (173, 'Nokia - 6010', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (174, 'Nokia - 6020', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (175, 'Nokia - 6021', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (176, 'Nokia - 6030', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (177, 'Nokia - 6060', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (178, 'Nokia - 6070', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (179, 'Nokia - 6080', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (180, 'Nokia - 6085', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (181, 'Nokia - 6086', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (182, 'Nokia - 6100', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (183, 'Nokia - 6101', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (184, 'Nokia - 6102', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (185, 'Nokia - 6102i', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (186, 'Nokia - 6103', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (187, 'Nokia - 6108', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (188, 'Nokia - 6111', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (189, 'Nokia - 6125', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (190, 'Nokia - 6126', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (191, 'Nokia - 6131', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (192, 'Nokia - 6131_NFC', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (193, 'Nokia - 6133', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (194, 'Nokia - 6136', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (195, 'Nokia - 6151', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (196, 'Nokia - 6152', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (197, 'Nokia - 6155', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (198, 'Nokia - 6155i', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (199, 'Nokia - 6165', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (200, 'Nokia - 6170', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (201, 'Nokia - 6200', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (202, 'Nokia - 6220', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (203, 'Nokia - 6230', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (204, 'Nokia - 6230i', '208', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (205, 'Nokia - 6233', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (206, 'Nokia - 6234', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (207, 'Nokia - 6235', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (208, 'Nokia - 6235i', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (209, 'Nokia - 6255', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (210, 'Nokia - 6260', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (211, 'Nokia - 6265', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (212, 'Nokia - 6265i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (213, 'Nokia - 6270', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (214, 'Nokia - 6275', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (215, 'Nokia - 6275i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (216, 'Nokia - 6280', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (217, 'Nokia - 6282', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (218, 'Nokia - 6288', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (219, 'Nokia - 6290', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (220, 'Nokia - 6300', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (221, 'Nokia - 6310i', '96', '65', '1', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (222, 'Nokia - 6560', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (223, 'Nokia - 6600', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (224, 'Nokia - 6610', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (225, 'Nokia - 6610i', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (226, 'Nokia - 6620', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (227, 'Nokia - 6630', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (228, 'Nokia - 6650', '128', '160', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (229, 'Nokia - 6651', '128', '160', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (230, 'Nokia - 6670', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (231, 'Nokia - 6680', '176', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (232, 'Nokia - 6681', '176', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (233, 'Nokia - 6682', '176', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (234, 'Nokia - 6800', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (235, 'Nokia - 6810', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (236, 'Nokia - 6820', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (237, 'Nokia - 6822', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (238, 'Nokia - 7200', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (239, 'Nokia - 7210', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (240, 'Nokia - 7250', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (241, 'Nokia - 7250i', '128', '128', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (242, 'Nokia - 7260', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (243, 'Nokia - 7270', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (244, 'Nokia - 7360', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (245, 'Nokia - 7370', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (246, 'Nokia - 7373', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (247, 'Nokia - 7390', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (248, 'Nokia - 7600', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (249, 'Nokia - 7610', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (250, 'Nokia - 7650', '176', '208', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (251, 'Nokia - 7700', '640', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (252, 'Nokia - 7710', '640', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (253, 'Nokia - 8800', '208', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (254, 'Nokia - 8800_Sirocco_Edition', '208', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (255, 'Nokia - 8801', '208', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (256, 'Nokia - 8910i', '96', '65', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (257, 'Nokia - 9300', '640', '200', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (258, 'Nokia - 9300i', '640', '200', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (259, 'Nokia - 9500', '640', '200', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (260, 'Nokia - E50', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (261, 'Nokia - E60', '352', '416', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (262, 'Nokia - E61', '320', '240', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (263, 'Nokia - E62', '320', '240', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (264, 'Nokia - E70', '352', '416', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (265, 'Nokia - Midp2Cldc11', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (266, 'Nokia - N-Gage', '176', '208', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (267, 'Nokia - N-Gage_QD', '176', '208', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (268, 'Nokia - N3250', '176', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (269, 'Nokia - N70', '176', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (270, 'Nokia - N71', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (271, 'Nokia - N72', '176', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (272, 'Nokia - N73', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (273, 'Nokia - N75', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (274, 'Nokia - N76', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (275, 'Nokia - N80', '352', '416', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (276, 'Nokia - N90', '352', '416', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (277, 'Nokia - N91', '176', '208', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (278, 'Nokia - N92', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (279, 'Nokia - N93', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (280, 'Nokia - N93i', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (281, 'Nokia - N95', '240', '320', '24', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (282, 'Nokia - Series40', '128', '128', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (283, 'Nokia - Series40DP1', '128', '128', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (284, 'Nokia - Series40DP2', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (285, 'Nokia - Series40DP3', '240', '320', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (286, 'Nokia - Series40E3', '240', '320', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (287, 'Nokia - Series40Midp2', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (288, 'Nokia - Series60', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (289, 'Nokia - Series60E1', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (290, 'Nokia - Series60E2', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (291, 'Nokia - Series60E2FP1', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (292, 'Nokia - Series60E2FP2', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (293, 'Nokia - Series60E2FP3', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (294, 'Nokia - Series60E3', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (295, 'Nokia - Series60Midp2', '176', '208', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (296, 'Nokia - Series80', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (297, 'Nokia - Series90', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (298, 'O2 - XDAII', '240', '247', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (299, 'Palm - TungstenC', '300', '300', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (300, 'Panasonic - C3003P', '132', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (301, 'Panasonic - X60', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (302, 'Qtek - 8310', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (303, 'Qtek - 9100', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (304, 'Qtek - XDAII', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (305, 'Research In Motion - BlackBerry 5810', '160', '160', '1', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (306, 'Research In Motion - BlackBerry 5820', '160', '160', '1', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (307, 'Sagem - My300x', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (308, 'Sagem - My400x', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (309, 'Sagem - My700x', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (310, 'Sagem - MyC4', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (311, 'Sagem - MyC5-2', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (312, 'Sagem - MyV-55', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (313, 'Sagem - MyV-65', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (314, 'Sagem - MyV-75', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (315, 'Sagem - MyW-7', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (316, 'Sagem - MyX-4', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (317, 'Sagem - MyX-8', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (318, 'Sagem - MyX5-2', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (319, 'Sagem - MyX6', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (320, 'Sagem - MyX6-2', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (321, 'Sagem - MyX7', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (322, 'Sagem - MyZ-5', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (323, 'Samsung - C100', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (324, 'Samsung - C110', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (325, 'Samsung - D100', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (326, 'Samsung - D108', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (327, 'Samsung - D410', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (328, 'Samsung - D415', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (329, 'Samsung - D418', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (330, 'Samsung - E100A', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (331, 'Samsung - E105', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (332, 'Samsung - E108', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (333, 'Samsung - E300', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (334, 'Samsung - E310', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (335, 'Samsung - E400', '128', '144', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (336, 'Samsung - E418', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (337, 'Samsung - E600', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (338, 'Samsung - E700A', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (339, 'Samsung - E708', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (340, 'Samsung - E710', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (341, 'Samsung - E715', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (342, 'Samsung - E800C', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (343, 'Samsung - E808', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (344, 'Samsung - E810', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (345, 'Samsung - P400', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (346, 'Samsung - P730', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (347, 'Samsung - P735', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (348, 'Samsung - S100', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (349, 'Samsung - S105', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (350, 'Samsung - S200', '128', '144', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (351, 'Samsung - S208', '128', '144', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (352, 'Samsung - S300', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (353, 'Samsung - S300M', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (354, 'Samsung - S307', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (355, 'Samsung - SCH-X130', '128', '128', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (356, 'Samsung - SCH-X230', '120', '160', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (357, 'Samsung - SCH-X250', '120', '160', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (358, 'Samsung - SCH-X350', '128', '128', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (359, 'Samsung - SGH-A700', '178', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (360, 'Samsung - SGH-C130', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (361, 'Samsung - SGH-D500', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (362, 'Samsung - SGH-D520', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (363, 'Samsung - SGH-D600', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (364, 'Samsung - SGH-D608', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (365, 'Samsung - SGH-D720', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (366, 'Samsung - SGH-D730', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (367, 'Samsung - SGH-D800', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (368, 'Samsung - SGH-D820', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (369, 'Samsung - SGH-D828', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (370, 'Samsung - SGH-D900', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (371, 'Samsung - SGH-D908', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (372, 'Samsung - SGH-E100', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (373, 'Samsung - SGH-E330', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (374, 'Samsung - SGH-E380', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (375, 'Samsung - SGH-E388', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (376, 'Samsung - SGH-E390', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (377, 'Samsung - SGH-E490', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (378, 'Samsung - SGH-E500', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (379, 'Samsung - SGH-E530', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (380, 'Samsung - SGH-E570', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (381, 'Samsung - SGH-E600', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (382, 'Samsung - SGH-E630', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (383, 'Samsung - SGH-E700', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (384, 'Samsung - SGH-E720', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (385, 'Samsung - SGH-E730', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (386, 'Samsung - SGH-E760', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (387, 'Samsung - SGH-E770', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (388, 'Samsung - SGH-E780', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (389, 'Samsung - SGH-E788', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (390, 'Samsung - SGH-E800', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (391, 'Samsung - SGH-E810', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (392, 'Samsung - SGH-E820', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (393, 'Samsung - SGH-E870', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (394, 'Samsung - SGH-E898', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (395, 'Samsung - SGH-E900', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (396, 'Samsung - SGH-P300', '240', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (397, 'Samsung - SGH-P310', '320', '240', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (398, 'Samsung - SGH-P858', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (399, 'Samsung - SGH-S100', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (400, 'Samsung - SGH-T509', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (401, 'Samsung - SGH-T619', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (402, 'Samsung - SGH-T709', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (403, 'Samsung - SGH-T809', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (404, 'Samsung - SGH-X100', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (405, 'Samsung - SGH-X600', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (406, 'Samsung - SGH-X700', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (407, 'Samsung - SGH-X708', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (408, 'Samsung - SGH-X820', '220', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (409, 'Samsung - SGH-Z400', '240', '297', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (410, 'Samsung - SGH-Z540', '240', '297', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (411, 'Samsung - SGH-Z560', '240', '297', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (412, 'Samsung - SGH-Z720', '240', '297', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (413, 'Samsung - SGH-ZM60', '176', '205', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (414, 'Samsung - SPH-N400', '128', '96', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (415, 'Samsung - SPH-X4209', '128', '160', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (416, 'Samsung - X100A', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (417, 'Samsung - X105', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (418, 'Samsung - X108', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (419, 'Samsung - X400', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (420, 'Samsung - X426', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (421, 'Samsung - X427', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (422, 'Samsung - X430', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (423, 'Samsung - X450', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (424, 'Samsung - X458', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (425, 'Samsung - X600A', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (426, 'Samsung - X608', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (427, 'Samsung - Z100', '176', '177', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (428, 'Samsung - Z105', '176', '177', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (429, 'Sanyo - A3011SA', '132', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (430, 'Sanyo - SCP-4900', '120', '96', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (431, 'Sanyo - SCP-5300', '128', '132', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (432, 'Sendo - X', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (433, 'Sharp - 902', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (434, 'Sharp - GX10', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (435, 'Sharp - GX10i', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (436, 'Sharp - GX15', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (437, 'Sharp - GX20', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (438, 'Sharp - GX25', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (439, 'Sharp - GX30', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (440, 'Sharp - GX30i', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (441, 'Sharp - J-SH07', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (442, 'Sharp - J-SH08', '122', '162', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (443, 'Sharp - SGH-902', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (444, 'Sharp - TM100', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (445, 'Sharp - TM200', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (446, 'Siemens - 2128', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (447, 'Siemens - 3138', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (448, 'Siemens - A56i', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (449, 'Siemens - C55', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (450, 'Siemens - C56', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (451, 'Siemens - C60', '101', '80', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (452, 'Siemens - C61', '101', '80', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (453, 'Siemens - C65', '130', '130', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (454, 'Siemens - CF62', '130', '130', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (455, 'Siemens - CT56', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (456, 'Siemens - CX65', '132', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (457, 'Siemens - M46', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (458, 'Siemens - M50', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (459, 'Siemens - M55', '101', '80', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (460, 'Siemens - M56', '101', '80', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (461, 'Siemens - M65', '132', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (462, 'Siemens - MC60', '101', '80', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (463, 'Siemens - MT50', '101', '64', '2', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (464, 'Siemens - S55', '101', '80', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (465, 'Siemens - S56', '101', '80', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (466, 'Siemens - S57', '101', '80', '8', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (467, 'Siemens - S65', '132', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (468, 'Siemens - SK65', '132', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (469, 'Siemens - SL55', '101', '80', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (470, 'Siemens - SL56', '101', '80', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (471, 'Siemens - SL75', '132', '176', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (472, 'Siemens - ST60', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (473, 'Siemens - SX1', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (474, 'Siemens - SXG75', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (475, 'Siemens - midp1', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (476, 'Siemens - midp2', '', '', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (477, 'Siemens - x55', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (478, 'Siemens - x65', '132', '176', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (479, 'Siemens - x75', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (480, 'Sony-Ericsson - D750', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (481, 'Sony-Ericsson - F500', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (482, 'Sony-Ericsson - F500i', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (483, 'Sony-Ericsson - J300', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (484, 'Sony-Ericsson - JavaPlatform1', '128', '160', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (485, 'Sony-Ericsson - JavaPlatform1Symbian', '208', '320', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (486, 'Sony-Ericsson - JavaPlatform2', '176', '220', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (487, 'Sony-Ericsson - JavaPlatform2Symbian', '208', '320', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (488, 'Sony-Ericsson - JavaPlatform3', '', '', '','2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (489, 'Sony-Ericsson - JavaPlatform4', '176', '220', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (490, 'Sony-Ericsson - JavaPlatform5', '176', '220', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (491, 'Sony-Ericsson - JavaPlatform6', '176', '220', '', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (492, 'Sony-Ericsson - K300', '128', '128', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (493, 'Sony-Ericsson - K310', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (494, 'Sony-Ericsson - K320', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (495, 'Sony-Ericsson - K320i', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (496, 'Sony-Ericsson - K500', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (497, 'Sony-Ericsson - K500c', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (498, 'Sony-Ericsson - K500i', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (499, 'Sony-Ericsson - K510', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (500, 'Sony-Ericsson - K600', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (501, 'Sony-Ericsson - K608', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (502, 'Sony-Ericsson - K610', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (503, 'Sony-Ericsson - K700', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (504, 'Sony-Ericsson - K700c', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (505, 'Sony-Ericsson - K700i', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (506, 'Sony-Ericsson - K750', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (507, 'Sony-Ericsson - K790', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (508, 'Sony-Ericsson - K800', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (509, 'Sony-Ericsson - K800i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (510, 'Sony-Ericsson - M600', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (511, 'Sony-Ericsson - Mylo', '320', '240', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (512, 'Sony-Ericsson - P800', '208', '320', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (513, 'Sony-Ericsson - P802', '208', '320', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (514, 'Sony-Ericsson - P900', '208', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (515, 'Sony-Ericsson - P908', '208', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (516, 'Sony-Ericsson - P910', '208', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (517, 'Sony-Ericsson - P910a', '208', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (518, 'Sony-Ericsson - P910c', '208', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (519, 'Sony-Ericsson - P910i', '208', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (520, 'Sony-Ericsson - P990i', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (521, 'Sony-Ericsson - S700', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (522, 'Sony-Ericsson - S700c', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (523, 'Sony-Ericsson - S700i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (524, 'Sony-Ericsson - T610', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (525, 'Sony-Ericsson - T616', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (526, 'Sony-Ericsson - T618', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (527, 'Sony-Ericsson - T628', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (528, 'Sony-Ericsson - T630', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (529, 'Sony-Ericsson - T637', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (530, 'Sony-Ericsson - V600', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (531, 'Sony-Ericsson - V630', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (532, 'Sony-Ericsson - V800', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (533, 'Sony-Ericsson - V802', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (534, 'Sony-Ericsson - W300', '128', '160', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (535, 'Sony-Ericsson - W550', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (536, 'Sony-Ericsson - W600', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (537, 'Sony-Ericsson - W710', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (538, 'Sony-Ericsson - W710i', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (539, 'Sony-Ericsson - W800', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (540, 'Sony-Ericsson - W810', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (541, 'Sony-Ericsson - W810i', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (542, 'Sony-Ericsson - W830', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (543, 'Sony-Ericsson - W830i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (544, 'Sony-Ericsson - W850', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (545, 'Sony-Ericsson - W850i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (546, 'Sony-Ericsson - W900', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (547, 'Sony-Ericsson - W900_emu', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (548, 'Sony-Ericsson - W900i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (549, 'Sony-Ericsson - W950i', '240', '320', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (550, 'Sony-Ericsson - Z1010', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (551, 'Sony-Ericsson - Z300', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (552, 'Sony-Ericsson - Z300a', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (553, 'Sony-Ericsson - Z500', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (554, 'Sony-Ericsson - Z500a', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (555, 'Sony-Ericsson - Z500i', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (556, 'Sony-Ericsson - Z520', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (557, 'Sony-Ericsson - Z525', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (558, 'Sony-Ericsson - Z530', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (559, 'Sony-Ericsson - Z600', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (560, 'Sony-Ericsson - Z608', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (561, 'Sony-Ericsson - Z710', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (562, 'Sony-Ericsson - Z800', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (563, 'T-Mobile - MDAII', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (564, 'T-Mobile - MDAcompact', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (565, 'T-Mobile - SDA', '176', '208', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (566, 'Toshiba - A3013T', '144', '176', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (567, 'Toshiba - C5001T', '144', '176', '12', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (568, 'Toshiba - J-T06', '', '', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (569, 'Vodafone - Motorola-V525', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (570, 'Vodafone - Motorola-V600', '176', '220', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (571, 'Vodafone - Sharp-GX10', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (572, 'Vodafone - Sharp-GX10i', '120', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (573, 'Vodafone - Sharp-GX20', '240', '320', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (574, 'Vodafone - Sony-Ericsson-F500i', '128', '160', '16', '2007-03-15 14:27:14','0000-00-00 00:00:00');
INSERT INTO `phones` VALUES (575, 'Vodafone - Sony-Ericsson-V600', '176', '220', '18', '2007-03-15 14:27:14','0000-00-00 00:00:00');
