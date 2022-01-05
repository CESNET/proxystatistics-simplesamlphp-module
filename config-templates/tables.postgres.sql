CREATE TABLE "statistics_sums" (
  "id" BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  "year" INTEGER NOT NULL,
  "month" INTEGER DEFAULT NULL,
  "day" INTEGER DEFAULT NULL,
  "idp_id" INTEGER NOT NULL DEFAULT 0,
  "sp_id" INTEGER NOT NULL DEFAULT 0,
  "logins" INTEGER DEFAULT NULL,
  "users" INTEGER DEFAULT NULL,
  CONSTRAINT "year" UNIQUE ("year","month","day","idp_id","sp_id")
);

CREATE INDEX "statistics_sums_idp_id" ON "statistics_sums" ("idp_id");
CREATE INDEX "statistics_sums_sp_id" ON "statistics_sums" ("sp_id");

CREATE TABLE "statistics_per_user" (
  "day" DATE NOT NULL,
  "idp_id" INTEGER NOT NULL,
  "sp_id" INTEGER NOT NULL,
  "user" VARCHAR(255) NOT NULL,
  "logins" INTEGER DEFAULT '1',
  CONSTRAINT primary_key PRIMARY KEY ("day","idp_id","sp_id","user")
);

CREATE INDEX "statistics_per_user_idp_id" ON "statistics_per_user" ("idp_id");
CREATE INDEX "statistics_per_user_sp_id" ON "statistics_per_user" ("sp_id");

CREATE TABLE "statistics_idp" (
  "idp_id" INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  "identifier" VARCHAR(255) NOT NULL UNIQUE,
  "name" VARCHAR(255) NOT NULL
);

CREATE TABLE "statistics_sp" (
  "sp_id" INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  "identifier" VARCHAR(255) NOT NULL UNIQUE,
  "name" VARCHAR(255) NOT NULL
);
