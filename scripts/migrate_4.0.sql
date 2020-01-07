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
  LEFT JOIN statistics_idp ON statistics_detail.sourceIdp = statistics_idp.identifier
  LEFT JOIN statistics_sp ON statistics_detail.service = statistics_sp.identifier
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
  spId;

INSERT INTO statistics_sums
SELECT
  NULL,
  YEAR(`day`),
  MONTH(`day`),
  DAY(`day`),
  NULL,
  spId,
  SUM(logins),
  COUNT(DISTINCT user) AS users
FROM
  statistics_per_user
GROUP BY
  `day`,
  spId;

INSERT INTO statistics_sums
SELECT
  NULL,
  YEAR(`day`),
  MONTH(`day`),
  DAY(`day`),
  idpId,
  NULL,
  SUM(logins),
  COUNT(DISTINCT user) AS users
FROM
  statistics_per_user
GROUP BY
  `day`,
  idpId;

INSERT INTO statistics_sums
SELECT
  NULL,
  YEAR(`day`),
  MONTH(`day`),
  DAY(`day`),
  NULL,
  NULL,
  SUM(logins),
  COUNT(DISTINCT user) AS users
FROM
  statistics_per_user
GROUP BY
  `day`;
