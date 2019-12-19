# daily and monthly logins and unique users for all combinations of idp+sp
# -> can be reduced depending on the mode (IdP mode does not need the combinations with IdP)
# (could also include yearly numbers if statistics_per_user are kept for a year)
CREATE TABLE `statistics_sums` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `year` YEAR NOT NULL,
  `month` TINYINT UNSIGNED DEFAULT NULL,
  `day` TINYINT UNSIGNED DEFAULT NULL,
  `idpId` INT UNSIGNED DEFAULT NULL,
  `spId` INT UNSIGNED DEFAULT NULL,
  `logins` INT UNSIGNED DEFAULT NULL,
  `users` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year` (`year`,`month`,`day`,`idpId`,`spId`),
  KEY `idpId` (`idpId`),
  KEY `spId` (`spId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
# ROWS
# each row contains daily users = COUNT(1) and daily logins = SUM(logins) from statistics_per_user
# year, month, day,  idp,  sp    | daily per idp+sp
# year, month, day,  NULL, sp    | daily per sp
# year, month, day,  idp,  NULL  | daily per idp
# year, month, day,  NULL, NULL  | daily (total)
# year, month, NULL, -||-        | monthly -||-

# daily logins per IdP+SP+user combination
# data is being kept for ~1 month
CREATE TABLE `statistics_per_user` (
  `day` date NOT NULL,
  `idpId` INT UNSIGNED NOT NULL,
  `spId` INT UNSIGNED NOT NULL,
  `user` VARCHAR(255) NOT NULL,
  `logins` INT UNSIGNED DEFAULT '1',
  PRIMARY KEY (`day`,`idpId`,`spId`,`user`),
  KEY `idpId` (`idpId`),
  KEY `spId` (`spId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# identity providers
CREATE TABLE `statistics_idp` (
  `idpId` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entityId` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`idpId`),
  UNIQUE KEY `entityId` (`entityId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# services
CREATE TABLE `statistics_sp` (
  `spId` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`spId`),
  UNIQUE KEY `identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
