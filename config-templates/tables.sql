--Statistics for IdPs
CREATE TABLE statistics (
    year INT NOT NULL,
    month INT NOT NULL,
    day INT NOT NULL,
    sourceIdp VARCHAR(255) NOT NULL,
    service VARCHAR(255) NOT NULL,
    count INT,
    INDEX (sourceIdp),
    INDEX (service),
    INDEX (year),
    INDEX (year,month),
    INDEX (year,month,day),
    PRIMARY KEY (year, month, day, sourceIdp, service)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--Tables for mapping identifier to name
CREATE TABLE identityProvidersMap(
	entityId VARCHAR(255) NOT NULL,
	name VARCHAR(255) NOT NULL,
	PRIMARY KEY (entityId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE serviceProvidersMap(
	identifier VARCHAR(255) NOT NULL,
	name VARCHAR(255) NOT NULL,
	PRIMARY KEY (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
