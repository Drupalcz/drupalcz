# Will remove most of the content to make DB smaller.

# CONSEQUENCES:

# Result includes these content types:
# story
# blog
# forum
# content_katalog
# faq
# bezpecnost
# content_webhosting
# poll
# lecture

# Result does NOT include these content types:
# company
# katalog
# page

# No hyerarchycal comments!

# Delete some users and content.
DELETE FROM users WHERE uid > '100';
DELETE FROM profile_values WHERE uid > '100';
DELETE FROM authmap WHERE uid > '100';
DELETE FROM history WHERE uid > '100';
# Delete commets of deleted users
DELETE FROM comments WHERE uid > '100' AND pid != '0';
# Delete most of anonymous comments.
DELETE FROM comments WHERE uid = '0' AND cid > '1000';

# Location instance
DELETE FROM location_instance WHERE lid IN (
  SELECT * FROM (
    SELECT location_instance.lid FROM location_instance JOIN location ON location_instance.lid=location.lid WHERE location_instance.uid > '100'
  ) AS lid
);
# Location
DELETE FROM location WHERE lid NOT IN (
  SELECT lid FROM location_instance AS lid
);

# ToDo: Make these tables slimmer:
# node_authorship
# url_alias
# poll_votes

# No impact on migration, just making the DB smaller.
TRUNCATE TABLE ads;
TRUNCATE TABLE ad_clicks;
TRUNCATE TABLE ad_owners;
TRUNCATE TABLE ad_statistics;
TRUNCATE TABLE locales_source;
TRUNCATE TABLE locales_target;
