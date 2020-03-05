# import
INSERT INTO statistics_idp (`identifier`, `name`)
SELECT
  `entityID`,
  `name`
FROM
  identityProvidersMap;

INSERT INTO statistics_idp (`identifier`, `name`)
SELECT
  DISTINCT `sourceIdp`,
  `sourceIdp`
FROM
  statistics_detail
WHERE
  `sourceIdp` NOT IN (
    SELECT
      `identifier`
    FROM
      statistics_idp
  );

INSERT INTO statistics_sp (`identifier`, `name`)
SELECT
  `identifier`,
  `name`
FROM
  serviceProvidersMap;

INSERT INTO statistics_sp (`identifier`, `name`)
SELECT
  DISTINCT `service`,
  `service`
FROM
  statistics_detail
WHERE
  `service` NOT IN (
    SELECT
      `identifier`
    FROM
      statistics_sp
  );

INSERT INTO statistics_per_user (
  `day`, `idpId`, `spId`, `user`, `logins`
)
SELECT
  STR_TO_DATE(
    CONCAT(`year`, '-', `month`, '-', `day`),
    '%Y-%m-%d'
  ),
  `idpId`,
  `spId`,
  `user`,
  `count`
FROM
  statistics_detail
  JOIN statistics_idp ON statistics_detail.sourceIdp = statistics_idp.identifier
  JOIN statistics_sp ON statistics_detail.service = statistics_sp.identifier
GROUP BY
  `year`,
  `month`,
  `day`,
  `sourceIdp`,
  `service`,
  `user`;

# aggregation
INSERT INTO statistics_sums
SELECT
  NULL,
  YEAR(`day`),
  MONTH(`day`),
  DAY(`day`),
  idpId,
  spId,
  SUM(logins),
  COUNT(DISTINCT user) AS users
FROM
  statistics_per_user
GROUP BY
  `day`,
  idpId,
  spId
HAVING day < DATE(NOW());

INSERT INTO statistics_sums
SELECT
  NULL,
  YEAR(`day`),
  MONTH(`day`),
  DAY(`day`),
  0,
  spId,
  SUM(logins),
  COUNT(DISTINCT user) AS users
FROM
  statistics_per_user
GROUP BY
  `day`,
  spId
HAVING day < DATE(NOW());

INSERT INTO statistics_sums
SELECT
  NULL,
  YEAR(`day`),
  MONTH(`day`),
  DAY(`day`),
  idpId,
  0,
  SUM(logins),
  COUNT(DISTINCT user) AS users
FROM
  statistics_per_user
GROUP BY
  `day`,
  idpId
HAVING day < DATE(NOW());

INSERT INTO statistics_sums
SELECT
  NULL,
  YEAR(`day`),
  MONTH(`day`),
  DAY(`day`),
  0,
  0,
  SUM(logins),
  COUNT(DISTINCT user) AS users
FROM
  statistics_per_user
GROUP BY
  `day`
HAVING day < DATE(NOW());


# add older stats
INSERT INTO statistics_sums (`year`, `month`, `day`, `idpId`, `spId`, `logins`, `users`)
SELECT
  `year`,
  `month`,
  `day`,
  `idpId`,
  `spId`,
  `count`,
  NULL
FROM
  statistics
  JOIN statistics_idp ON statistics.sourceIdp = statistics_idp.identifier
  JOIN statistics_sp ON statistics.service = statistics_sp.identifier
GROUP BY
  `year`,
  `month`,
  `day`,
  `sourceIdp`,
  `service`
ON DUPLICATE KEY UPDATE id=id;
# or if you want to merge, ON DUPLICATE KEY UPDATE logins=logins+VALUES(logins)

INSERT INTO statistics_sums (`year`, `month`, `day`, `idpId`, `spId`, `logins`, `users`)
SELECT
  `year`,
  `month`,
  `day`,
  0,
  `spId`,
  SUM(`count`),
  NULL
FROM
  statistics
  JOIN statistics_idp ON statistics.sourceIdp = statistics_idp.identifier
  JOIN statistics_sp ON statistics.service = statistics_sp.identifier
GROUP BY
  `year`,
  `month`,
  `day`,
  `service`
ON DUPLICATE KEY UPDATE id=id;
# or if you want to merge, ON DUPLICATE KEY UPDATE logins=logins+VALUES(logins)

INSERT INTO statistics_sums (`year`, `month`, `day`, `idpId`, `spId`, `logins`, `users`)
SELECT
  `year`,
  `month`,
  `day`,
  `idpId`,
  0,
  SUM(`count`),
  NULL
FROM
  statistics
  JOIN statistics_idp ON statistics.sourceIdp = statistics_idp.identifier
  JOIN statistics_sp ON statistics.service = statistics_sp.identifier
GROUP BY
  `year`,
  `month`,
  `day`,
  `sourceIdp`
ON DUPLICATE KEY UPDATE id=id;
# or if you want to merge, ON DUPLICATE KEY UPDATE logins=logins+VALUES(logins)

INSERT INTO statistics_sums (`year`, `month`, `day`, `idpId`, `spId`, `logins`, `users`)
SELECT
  `year`,
  `month`,
  `day`,
  0,
  0,
  SUM(`count`),
  NULL
FROM
  statistics
  JOIN statistics_idp ON statistics.sourceIdp = statistics_idp.identifier
  JOIN statistics_sp ON statistics.service = statistics_sp.identifier
GROUP BY
  `year`,
  `month`,
  `day`
ON DUPLICATE KEY UPDATE id=id;
# or if you want to merge, ON DUPLICATE KEY UPDATE logins=logins+VALUES(logins)
