ALTER TABLE statistics_sums RENAME "idpId" TO idp_id;
ALTER TABLE statistics_sums RENAME "spId" TO sp_id;

ALTER TABLE statistics_per_user RENAME "idpId" TO idp_id;
ALTER TABLE statistics_per_user RENAME "spId" TO sp_id;

ALTER TABLE statistics_idp RENAME "idpId" TO idp_id;

ALTER TABLE statistics_sp RENAME "spId" TO sp_id;
