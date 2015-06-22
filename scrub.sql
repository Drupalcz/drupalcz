# Will Scrub a Drupal.cz database.
# Jakub Suchy, 22.10.2010

# Drupal core
UPDATE authmap SET authname = CONCAT('http://aid', aid, '.uid', uid, '.drupal.cz'), module = 'drupal' WHERE aid != '1';
UPDATE users SET name=CONCAT('user', uid), pass='heslo', init=CONCAT('user', uid, '@example.com') WHERE uid != 0;
UPDATE users SET mail=CONCAT('user', uid, '@example.com') WHERE uid != 0;
UPDATE comments SET name='Anonymous', mail='', homepage='http://example.com', hostname='1.1.1.1' WHERE uid=0;
UPDATE contact SET recipients = 'drupalcz@localhost';
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
TRUNCATE TABLE openid_association;

# Ad
UPDATE ads SET autoactivate = '0', autoactivated = '0', autoexpire = '0', autoexpired = '0', activated = '0', maxviews = '0', maxclicks = '0', expired = '0';
UPDATE ad_clicks SET uid = '0', status = '4', hostname = '127.0.0.1', user_agent = 'Drupal (+http://drupal.org/)', adgroup = '', hostid = '', url = '', timestamp = '280281600';
UPDATE ad_owners SET uid = '1';
UPDATE ad_statistics SET date = '280281600', count = '1';

# Boost
TRUNCATE TABLE boost_cache;

# Captcha
TRUNCATE TABLE captcha_sessions;

# CCK (Content)
TRUNCATE TABLE cache_content;

# Devel
TRUNCATE TABLE devel_queries;
TRUNCATE TABLE devel_times;

# Location
UPDATE location SET city = 'Schneekoppe', latitude = '50.735942477452205', longitude = '15.739728212356567';
TRUNCATE TABLE cache_location;

# Mollom
UPDATE variable SET value = 's:32:"aff4833333333m7a2363233333333333";' WHERE name = 'mollom_public_key';
UPDATE variable SET value = 's:32:"aff4833333333m7a2363233333333332";' WHERE name = 'mollom_private_key';
TRUNCATE TABLE cache_mollom;
TRUNCATE TABLE mollom;

# Node Authorship
UPDATE node_authorship SET authorship = 'Drupal user';

# Path
DELETE FROM url_alias WHERE src LIKE 'user/%';

# Views
TRUNCATE TABLE cache_views;
TRUNCATE TABLE cache_views_data;
TRUNCATE TABLE views_object_cache;
