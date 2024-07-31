-- smazání uživatelů, co nic nemají
DELETE
FROM mzk.user
WHERE id NOT IN (SELECT user_id FROM mzk.user_resource)
  AND id NOT IN (SELECT user_id FROM mzk.search)
  AND id NOT IN (SELECT user_id FROM mzk.user_list)
  AND id NOT IN (SELECT user_id FROM mzk.comments)
  AND id NOT IN (SELECT user_id FROM mzk.resource_tags)
;

-- deduplikace uživatelů
CREATE INDEX user_deduplication_idx ON mzk.user(cat_username, id);

CREATE TABLE mzk.user_dedup AS (
  SELECT u1.id new_id, u2.id old_id
  FROM mzk.user u1
    JOIN mzk.user u2 ON u2.cat_username = u1.cat_username
  WHERE u1.id > u2.id
);

-- navazáni záznamů na stejného uživatele z duplicit
UPDATE mzk.user_resource upd
SET user_id = (SELECT dedup.new_id FROM mzk.user_dedup dedup WHERE dedup.old_id = upd.user_id)
WHERE user_id IN (SELECT old_id FROM mzk.user_dedup);

UPDATE mzk.search upd
SET user_id = (SELECT dedup.new_id FROM mzk.user_dedup dedup WHERE dedup.old_id = upd.user_id)
WHERE user_id IN (SELECT old_id FROM mzk.user_dedup);

UPDATE mzk.user_list upd
SET user_id = (SELECT dedup.new_id FROM mzk.user_dedup dedup WHERE dedup.old_id = upd.user_id)
WHERE user_id IN (SELECT old_id FROM mzk.user_dedup);

UPDATE mzk.comments upd
SET user_id = (SELECT dedup.new_id FROM mzk.user_dedup dedup WHERE dedup.old_id = upd.user_id)
WHERE user_id IN (SELECT old_id FROM mzk.user_dedup);

UPDATE mzk.resource_tags upd
SET user_id = (SELECT dedup.new_id FROM mzk.user_dedup dedup WHERE dedup.old_id = upd.user_id)
WHERE user_id IN (SELECT old_id FROM mzk.user_dedup);

-- a teď duplicity smažeme
DELETE FROM mzk.user WHERE id IN (SELECT old_id FROM mzk.user_dedup);

-- Zkontrolovat, že následující dotazy nic nevrátí
SELECT *
FROM mzk.user
WHERE id NOT IN (SELECT user_id FROM mzk.user_resource)
  AND id NOT IN (SELECT user_id FROM mzk.search)
  AND id NOT IN (SELECT user_id FROM mzk.user_list)
  AND id NOT IN (SELECT user_id FROM mzk.comments)
  AND id NOT IN (SELECT user_id FROM mzk.resource_tags)
;

SELECT username, COUNT(1)
FROM mzk.user
GROUP BY username
HAVING COUNT(1) > 1
;

SELECT *
FROM mzk.user
WHERE cat_username = ''
;

-- Smazat neuložená vyhledávání
DELETE FROM mzk.search WHERE saved = 0;

-- Sloupec pro nové zmigrované ID
ALTER TABLE mzk.user ADD COLUMN mig_id INT(11);

-- Migrace uživatelů - již existující uživatelé
UPDATE mzk.user upd
SET mig_id = (SELECT uc.user_id FROM vufind_mzk.user_card uc WHERE uc.cat_username = CONCAT('mzk.', upd.cat_username))
WHERE upd.id IN (
  SELECT u.id
  FROM mzk.user u
         JOIN vufind_mzk.user_card uc ON uc.cat_username = CONCAT('mzk.', u.cat_username)
);

UPDATE mzk.user upd
SET mig_id = (SELECT u.id FROM vufind_mzk.user u WHERE u.username = upd.username)
WHERE upd.mig_id IS NULL
  AND upd.username IN (SELECT u.username FROM vufind6.user u)
;

-- vytvoření nových uživatelů
INSERT INTO vufind6.user(username, password, firstname, lastname, email, cat_username,
  college, major, home_library, created, verify_hash, last_login, auth_method, pending_email,
  user_provided_email, last_language)
SELECT
  u.username,
  '' password,
  u.firstname firstname,
  u.lastname lastname,
  u.email,
  CONCAT('mzk.', u.cat_username) cat_username,
  u.college,
  u.major,
  'mzk' home_library,
  CAST(u.created AS DATETIME) created,
  '' verify_hash,
  CAST(u.created AS DATETIME) last_login,
  'shibboleth' auth_method,
  '' pending_email,
  false user_provided_email,
  'cs' last_language
FROM mzk.user u
WHERE u.mig_id IS NULL
;

UPDATE mzk.user upd
SET mig_id = (SELECT u.id FROM vufind_mzk.user u WHERE u.username = upd.username)
WHERE upd.mig_id IS NULL
  AND upd.username IN (SELECT u.username FROM vufind6.user u)
;

-- Ověřit, že následující dotaz nic nevrátí
SELECT * FROM mzk.user WHERE mig_id IS NULL;

-- Vytvoření nových uživatelů
INSERT INTO vufind6.user_card(user_id, card_name, cat_username, home_library, created, saved, eppn, edu_person_unique_id)
SELECT
  u.mig_id user_id,
  'mzk' card_name,
  CONCAT('mzk.', u.cat_username) cat_username,
  'mzk' home_library,
  u.created created,
  u.created saved,
  u.username eppn,
  u.username edu_person_unique_id
FROM mzk.user u
WHERE NOT EXISTS(SELECT 1 FROM vufind6.user_card uc WHERE uc.cat_username = CONCAT('mzk.', u.cat_username))
  AND NOT EXISTS(SELECT 1 FROM vufind6.user_card uc WHERE uc.eppn = u.username)
  AND u.cat_username IS NOT NULL
;

-- Uložená vyhledávání
ALTER TABLE vufind6.search ADD COLUMN search_object_orig BLOB;
ALTER TABLE vufind6.search ADD COLUMN migrate INT(1) DEFAULT 0;

-- Smazat uložená vyhledávání z EBSCO
DELETE FROM mzk.search WHERE id IN (18737174, 19551358, 19765290);

INSERT INTO vufind6.search(user_id, session_id, created, title, saved, search_object,
  notification_frequency, last_notification_sent, notification_base_url, search_object_orig, migrate)
SELECT
  u.mig_id user_id,
  s.session_id,
  s.created,
  s.title,
  s.saved,
  s.search_object,
  0 notification_frequency,
  '1999-12-31 23:00:00.0' last_notification_sent,
  '' notification_base_url,
  s.search_object search_object_orig,
  1 migrate
FROM mzk.search s
  JOIN mzk.user u ON u.id = s.user_id
;

-- Smazat nezmigrovatelná vyhledávání s MixedList
DELETE FROM vufind6.search WHERE CAST(search_object AS VARCHAR(4096)) LIKE '%s:2:"cl";s:9:"MixedList";%' AND saved = 1 AND migrate = 1;

-- Oblíbené - ID pro migraci
ALTER TABLE vufind6.resource ADD COLUMN mig_id INT(11);
ALTER TABLE vufind6.user_list ADD COLUMN mig_id INT(11);
ALTER TABLE vufind6.user_resource ADD COLUMN mig_id INT(11);

CREATE INDEX resource_mig_idx ON vufind6.resource(mig_id);
CREATE INDEX user_list_mig_idx ON vufind6.user_list(mig_id);
CREATE INDEX user_resource_mig_idx ON vufind6.user_resource(mig_id);

CREATE TABLE vufind6.resource_mig (
  new_record_id          VARCHAR(255),
  old_record_id          VARCHAR(255),
  new_resource_id        INT(11),
  old_resource_id        INT(11),
  dedup_old_resource_id  INT(11)
);

INSERT INTO vufind6.resource_mig(new_record_id, old_record_id, old_resource_id)
SELECT
  mu.record_id new_record_id,
  mu.record_id old_record_id,
  mu.id old_resource_id
FROM mzk.resource mu;

UPDATE resource_mig
SET new_record_id = CONCAT('mzk.', old_record_id)
WHERE old_record_id LIKE 'MZK01-%' OR old_record_id LIKE 'MZK03-%';
;

UPDATE resource_mig
SET new_record_id = REPLACE(old_record_id, 'bookport-mzk.', 'bookport.')
WHERE old_record_id LIKE 'bookport-mzk.%';

UPDATE resource_mig mig
SET new_record_id = REPLACE(old_record_id, 'MZK04-', 'unmz.')
WHERE mig.new_record_id LIKE 'MZK04-%';

UPDATE vufind6.resource_mig vrm SET new_resource_id = (SELECT id FROM vufind6.resource vr WHERE vr.record_id = vrm.new_record_id);

UPDATE vufind6.resource_mig vrm
SET dedup_old_resource_id = (
  SELECT id
  FROM mzk.resource mu
  WHERE vrm.old_record_id = mu.record_id
  ORDER BY (CASE WHEN mu.title IS NOT NULL AND mu.title != '' THEN 1 ELSE 0 END) DESC, mu.id DESC LIMIT 1
);

-- Migrace uživatelských záznamů
INSERT INTO vufind6.resource(record_id, title, author, author_sort, year, source, mig_id)
SELECT
  vrm.new_record_id record_id,
  SUBSTRING(mr.title, 0, 255) title,
  mr.author,
  CAST(NULL AS VARCHAR(128)) author_sort,
  mr.year,
  mr.source,
  mr.id mig_id
FROM vufind6.resource_mig vrm
  JOIN mzk.resource mr ON mr.id = vrm.old_resource_id
WHERE vrm.dedup_old_resource_id = vrm.old_resource_id
  AND vrm.new_resource_id IS NULL
;

UPDATE vufind6.resource_mig vrm
SET new_resource_id = (SELECT id FROM vufind6.resource vr WHERE vr.record_id = vrm.new_record_id LIMIT 1)
WHERE vrm.new_resource_id IS NULL
;

-- Migrace uživatelských seznamů
INSERT INTO vufind6.user_list(user_id, title, description, created, public, mig_id)
SELECT us.mig_id user_id, ul.title, ul.description, ul.created, ul.public, ul.id mig_id
FROM mzk.user_list ul
  JOIN mzk.user us ON us.id = ul.user_id
;

-- Migrace oblíbených záznamů
INSERT INTO vufind6.user_resource(user_id, resource_id, list_id, notes, saved, mig_id)
SELECT
  mu.mig_id user_id,
  vrm.new_resource_id resource_id,
  vul.id list_id,
  mur.notes notes,
  mur.saved saved,
  mur.id mig_id
FROM mzk.user_resource mur
  JOIN vufind6.resource_mig vrm ON vrm.old_resource_id = mur.resource_id
  JOIN mzk.user mu ON mu.id = mur.user_id
  JOIN mzk.resource mr ON mr.id = vrm.old_resource_id
  LEFT JOIN vufind6.user_list vul ON vul.mig_id = mur.list_id
;
