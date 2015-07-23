# Will Scrub a Drupal.cz database.
# Jakub Suchy, 22.10.2010

# Drupal core
UPDATE authmap SET authname = CONCAT('http://aid', aid, '.uid', uid, '.drupal.cz'), module = 'drupal' WHERE aid != '1';
UPDATE users SET name=CONCAT('user', uid), pass='heslo', init=CONCAT('user', uid, '@example.com') WHERE uid != 0;
UPDATE users SET mail=CONCAT('user', uid, '@example.com') WHERE uid != 0;
UPDATE comments SET name='Anonymous', mail='', homepage='http://example.com', hostname='1.1.1.1' WHERE uid=0;
UPDATE contact SET recipients = 'drupalcz@localhost';
UPDATE history SET timestamp = '280281600';
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

# User data
UPDATE profile_values SET value = 'Real Name' WHERE fid = '1';
UPDATE profile_values SET value = 'http://drupal.cz' WHERE fid = '4';
UPDATE profile_values SET value = '25-30' WHERE fid = '5';
UPDATE profile_values SET value = 'contributor' WHERE fid = '6';
UPDATE profile_values SET value = 'Google' WHERE fid = '7';
UPDATE profile_values SET value = 'Středně pokročilý' WHERE fid = '8';
UPDATE profile_values SET value = 'Jsem profesionál, umím dělat i moduly' WHERE fid = '9';
UPDATE profile_values SET value = 'Hlavní město Praha' WHERE fid = '10';
UPDATE profile_values SET value = 'drupalcz@localhost' WHERE fid = '11';

# Comments
UPDATE comments SET name=CONCAT('user', uid);
UPDATE comments SET mail=CONCAT('user', uid, '@example.com');
UPDATE comments SET homepage=CONCAT('http://user', uid, '.example.com');
# Note: Generates invalid IP addresses.
UPDATE comments SET hostname = CONCAT('127.0.', CAST(RAND() * 1000 AS INT), '.', CAST(RAND() * 1000 AS INT));
UPDATE comments SET timestamp=(timestamp + FLOOR(0 + (RAND() * 100000)));

# Varibles
DELETE FROM variable
WHERE (name = 'acquia_agent_cloud_migration' OR
name = 'acquia_agent_verify_peer' OR
name = 'acquia_identifier' OR
name = 'acquia_key' OR
name = 'acquia_migrate_files' OR
name = 'acquia_spi_boot_last' OR
name = 'acquia_spi_cron_last' OR
name = 'acquia_spi_module_rebuild' OR
name = 'acquia_subscription_data' OR
name = 'acquia_subscription_name');
UPDATE variable SET value = 's:32:"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";' WHERE name = 'boost_crawler_key';
UPDATE variable SET value = 's:64:"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";' WHERE name = 'drupal_private_key';
UPDATE variable SET value = 's:33:"aaaaaaaaaaaaaaaaaaaaa:aaaaaaaaaaa";' WHERE name = 'google_cse_cx';
UPDATE variable SET value = 's:86:"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";' WHERE name = 'googlemap_api_key';
UPDATE variable SET value = '' WHERE name = 'shield_user';
UPDATE variable SET value = '' WHERE name = 'shield_pass';

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
UPDATE variable SET value = 's:32:"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";' WHERE name = 'mollom_public_key';
UPDATE variable SET value = 's:32:"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";' WHERE name = 'mollom_private_key';
TRUNCATE TABLE cache_mollom;
TRUNCATE TABLE mollom;

# Node Authorship
UPDATE node_authorship SET authorship = 'Drupal user';

# Path
DELETE FROM url_alias WHERE src LIKE 'user/%';

# Poll
# Note: Generates invalid IP addresses.
UPDATE poll_votes SET uid = '0', hostname = CONCAT('127.0.', CAST(RAND() * 1000 AS INT), '.', CAST(RAND() * 1000 AS INT));

# Spam
UPDATE spam_tracker SET probability = '40', hostname = '127.0.0.1', hash = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', timestamp = '280281600';

# Voting API
UPDATE votingapi_vote SET uid = '1', timestamp = '280281600', hostname = '127.0.0.1';

# Views
TRUNCATE TABLE cache_views;
TRUNCATE TABLE cache_views_data;
TRUNCATE TABLE views_object_cache;

# Acquia
TRUNCATE TABLE __ACQUIA_MONITORING;
