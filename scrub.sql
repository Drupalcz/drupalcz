# Will Scrub a Drupal.cz database.
# Jakub Suchy, 22.10.2010


# Drupal core
UPDATE users SET name=CONCAT('user', uid), pass='heslo', init=CONCAT('user', uid, '@example.com') WHERE uid != 0;
UPDATE users SET mail=CONCAT('user', uid, '@example.com') WHERE uid != 0;
UPDATE comments SET name='Anonymous', mail='', homepage='http://example.com', hostname='1.1.1.1' WHERE uid=0;
UPDATE profile_values SET value = '1';
UPDATE variable SET value = 's:64:"aff4833333333m7a2363233333333332aff4833333333m7a2363233333333332";' WHERE name = 'drupal_private_key';
DELETE FROM watchdog;
DELETE FROM sessions;
DELETE FROM signup;
DELETE FROM signup_log;
TRUNCATE TABLE cache;
TRUNCATE TABLE cache_block;
TRUNCATE TABLE cache_filter;
TRUNCATE TABLE cache_path;
TRUNCATE TABLE cache_form;
TRUNCATE TABLE cache_page;
TRUNCATE TABLE cache_menu;
TRUNCATE TABLE cache_update;

# Location
UPDATE location SET city = 'Schneekoppe', latitude = '50.735942477452205', longitude = '15.739728212356567';

# Mollom
UPDATE variable SET value = 's:32:"aff4833333333m7a2363233333333333";' WHERE name = 'mollom_public_key';
UPDATE variable SET value = 's:32:"aff4833333333m7a2363233333333332";' WHERE name = 'mollom_private_key';

# Path
DELETE FROM url_alias WHERE src LIKE 'user/%';

