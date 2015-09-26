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

# Delete some users.
DELETE FROM users WHERE uid > '100';
DELETE FROM profile_values WHERE uid > '100';
DELETE FROM authmap WHERE uid > '100';
DELETE FROM history WHERE uid > '100';

# Nodes.
DELETE FROM node WHERE uid NOT IN (
  SELECT uid FROM users AS uid
);
# Make forum even smaller.
DELETE FROM node WHERE type = 'forum' AND uid = '0' AND nid < '5000';
# Node revisions.
DELETE FROM node_revisions WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
# Fields.
DELETE FROM content_field_download WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_field_gallery_image WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_field_popis_webu WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_field_screenshoty WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_field_staen WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_field_url WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_field_url_multiple WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
# Fields.
DELETE FROM content_type_ad WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_advpoll_binary WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_advpoll_ranking WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_banner WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_bezpecnost WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_book WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_company WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_content_katalog WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_content_webhosting WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_forum WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_gallery WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_image WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_katalog WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_lecture WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_page WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_poll WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
DELETE FROM content_type_story WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);

# Forum.
DELETE FROM forum WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);

# Delete comments of deleted users.
## And prevent "Lock wait timeout exceeded".
DELETE FROM comments WHERE uid > '4000';
DELETE FROM comments WHERE uid > '3000';
DELETE FROM comments WHERE uid > '2000';
DELETE FROM comments WHERE uid > '1000';
DELETE FROM comments WHERE uid > '100';
# Delete comments on deleted notes.
DELETE FROM comments WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);
# Delete most of anonymous comments.
DELETE FROM comments WHERE uid = '0' AND cid > '1000';
# Delete comments that are replies to deleted comments.
DROP PROCEDURE IF EXISTS fix_cmmnts;
delimiter //
CREATE PROCEDURE fix_cmmnts ()
BEGIN
DECLARE max int default 18;
DECLARE i int default 0;
  start transaction;
  while i < max do
    DELETE FROM comments WHERE pid !=0 AND pid NOT IN (
      SELECT * FROM (
        SELECT cid FROM comments
      ) AS cid
    );
    set i = i + 1;
  end while;
  commit;
END//
delimiter ;
CALL fix_cmmnts();

# Location instance.
DELETE FROM location_instance WHERE lid IN (
  SELECT * FROM (
    SELECT location_instance.lid FROM location_instance JOIN location ON location_instance.lid=location.lid WHERE location_instance.uid > '100'
  ) AS lid
);
# Location.
DELETE FROM location WHERE lid NOT IN (
  SELECT lid FROM location_instance AS lid
);

# Poll votes.
DELETE FROM poll_votes WHERE nid NOT IN (
  SELECT nid FROM node AS nid
);

# No impact on migration, just making the DB smaller.
TRUNCATE TABLE ads;
TRUNCATE TABLE ad_clicks;
TRUNCATE TABLE ad_owners;
TRUNCATE TABLE ad_statistics;
TRUNCATE TABLE aggregator_category_item;
TRUNCATE TABLE aggregator_feed;
TRUNCATE TABLE aggregator_item;
TRUNCATE TABLE aggregator_category_item;
TRUNCATE TABLE locales_source;
TRUNCATE TABLE locales_target;
TRUNCATE TABLE search_dataset;
TRUNCATE TABLE search_index;
TRUNCATE TABLE search_node_links;
TRUNCATE TABLE search_total;
TRUNCATE TABLE spam_tracker;
TRUNCATE TABLE url_alias;
