CREATE TABLE `user` (
  `id` int(5) NOT NULL auto_increment,
  `uname` VARCHAR(98) NOT NULL default '',
  `pw` VARCHAR(98) NOT NULL default '',
  `email` VARCHAR(100) NOT NULL default '',
  `date_joined` datetime NOT NULL default '0000-00-00 00:00:00',
  `ip` VARCHAR(20) NOT NULL default '',
  `level` VARCHAR(10) NOT NULL default '',
  `isbanned` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`id`)
);

CREATE TABLE `upload` (
`id` INT(5) NOT NULL AUTO_INCREMENT,
`owner` int(5) NOT NULL default 0,
`name` VARCHAR(30) NOT NULL default '',
`type` VARCHAR(30) NOT NULL default '',
`uri`  VARCHAR(255) NOT NULL default '',
`uuid`  VARCHAR(255) NOT NULL default '',
`title` VARCHAR(255) NOT NULL default '',
`size` INT NOT NULL default 0,
`content` LONGBLOB NOT NULL,
`thumbnail` BLOB NOT NULL,
`date_created` datetime NOT NULL default '0000-00-00 00:00:00',
`shared` enum('yes','no') NOT NULL default 'yes',
FOREIGN KEY (`owner`) REFERENCES user(`id`),
PRIMARY KEY(`id`)
);

