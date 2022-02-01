ALTER TABLE statistics_sums CHANGE idpId idp_id INT UNSIGNED NOT NULL;
ALTER TABLE statistics_sums CHANGE spId sp_id INT UNSIGNED NOT NULL;

ALTER TABLE statistics_per_user CHANGE idpId idp_id INT UNSIGNED NOT NULL;
ALTER TABLE statistics_per_user CHANGE spId sp_id INT UNSIGNED NOT NULL;

ALTER TABLE statistics_idp CHANGE idpId idp_id INT UNSIGNED NOT NULL;

ALTER TABLE statistics_sp CHANGE spId sp_id INT UNSIGNED NOT NULL;
