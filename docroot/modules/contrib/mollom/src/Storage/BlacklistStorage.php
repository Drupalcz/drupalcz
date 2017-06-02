<?php

namespace Drupal\mollom\Storage;

/**
 * Class BlacklistStorage is responsible for managing and retrieving a site's
 * blacklist.  It uses the DrupalClient for the Mollom API to handle operations.
 */
class BlacklistStorage {

  const TYPE_SPAM = 'spam';
  const TYPE_PROFANITY = 'profanity';
  const TYPE_UNWANTED = "unwanted";

  const CONTEXT_ALL_FIELDS = 'allFields';
  const CONTEXT_AUTHOR_FIELDS = 'author';
  const CONTEXT_AUTHOR_NAME = 'authorName';
  const CONTEXT_AUTHOR_MAIL = 'authorMail';
  const CONTEXT_AUTHOR_IP = 'authorIp';
  const CONTEXT_AUTHOR_ID = 'authorId';
  const CONTEXT_LINKS = 'links';
  const CONTEXT_POST_FIELDS = 'post';
  const CONTEXT_POST_TITLE = 'postTitle';
  const CONTEXT_POST_BODY = 'post';

  const MATCH_EXACT = 'exact';
  const MATCH_CONTAINS = 'contains';

  static private $blacklist = NULL;

  /**
   * Gets a blacklist of entries limited by type.
   *
   * @param $type
   *   The type of list to load; TYPE_SPAM, TYPE_PROFANITY, TYPE_UNWANTED.
   */
  static function getList($type = NULL) {
    if (!in_array($type, array(self::TYPE_SPAM, self::TYPE_PROFANITY, self::TYPE_UNWANTED))) {
      $type = NULL;
    }
    $list = self::loadList();
    if (!empty($type)) {
      $list = array_filter($list, function($entry) use ($type) {
        return $entry['reason'] === $type;
      });
    }
    return $list;
  }

  /**
   * Gets a blacklist entry.
   *
   * @param $entry_id
   *   The id of the blacklist entry to retrieve.
   *
   * @returns array
   *   An associative array of blacklist entry data.
   */
  static function getEntry($entry_id) {
    $result = \Drupal::service('mollom.client')->getBlacklistEntry($entry_id);
    return $result;
  }

  /**
   * Saves a blacklist entry on a blacklist.
   *
   *
   * Note: currently only adding is supported by the API, not updating.
   *
   * @param $entry
   *   An associative array of blacklist entry data.
   *   - created: A timestamp in seconds since the UNIX epoch of when the entry
   *     was created.
   *   - value: The blacklisted string/value.
   *   - reason: A string denoting the reason for why the value is blacklisted;
   *     one of 'spam', 'profanity', 'quality', or 'unwanted'. Defaults to
   *     'unwanted'.
   *   - context: A string denoting where the entry's value may match; one of
   *     'allFields', 'links', 'authorName', 'authorMail', 'authorIp',
   *     'authorIp', or 'postTitle'. Defaults to 'allFields'.
   *   - match: A string denoting how precise the entry's value may match; one
   *     of 'exact' or 'contains'. Defaults to 'contains'.
   *   - status: An integer denoting whether the entry is enabled (1) or not
   *     (0).
   *   - note: A custom string explaining the entry. Useful in a multi-moderator
   *     scenario.
   * @return bool
   *   True if successful, false if not.
   */
  static function saveEntry($entry) {
    $result = \Drupal::service('mollom.client')->saveBlacklistEntry($entry);
    return !empty($result['id']);
  }

  /**
   * Deletes a blacklist entry.
   *
   * @param $id
   *   The id of the blacklist entry to delete.
   * @response bool
   *   True if successful, false if not.
   */
  static function deleteEntry($id) {
    \Drupal::service('mollom.client')->deleteBlacklistEntry($id);
  }

  /**
   * Loads the blacklist for this site (all types).
   *
   * @return array
   *   The array of blacklist entries for this site.
   *
   * @todo Add error handling.
   */
  static private function loadList() {
    if (is_null(self::$blacklist)) {
      self::$blacklist = \Drupal::service('mollom.client')->getBlacklist();
    }
    return self::$blacklist;
  }
} 
