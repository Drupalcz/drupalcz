<?php

/**
 * Defines the client interface for a Mollom Drupal client.
 */

namespace Drupal\mollom\API;

interface DrupalClientInterface {

  /**
   * Loads a configuration value from client-side storage.
   *
   * @param string $name
   *   The configuration setting name to load, one of:
   *   - publicKey: The public API key for Mollom authentication.
   *   - privateKey: The private API key for Mollom authentication.
   *   - expectedLanguages: List of expected language codes for site content.
   *
   * @return mixed
   *   The stored configuration value or NULL if there is none.
   *
   * @see Mollom::saveConfiguration()
   * @see Mollom::deleteConfiguration()
   */
  function loadConfiguration($name);

  /**
   * Saves a configuration value to client-side storage.
   *
   * @param string $name
   *   The configuration setting name to save.
   * @param mixed $value
   *   The value to save.
   *
   * @see Mollom::loadConfiguration()
   * @see Mollom::deleteConfiguration()
   */
  function saveConfiguration($name, $value);

  /**
   * Deletes a configuration value from client-side storage.
   *
   * @param string $name
   *   The configuration setting name to delete.
   *
   * @see Mollom::loadConfiguration()
   * @see Mollom::saveConfiguration()
   */
  function deleteConfiguration($name);

  /**
   * Helper function to prepare a list of available languages.
   *
   * @return array
   *   An array of languages supported by Mollom with keys as the language code
   *   and values as the translated display values.
   */
  public static function getSupportedLanguages();

  /**
   * Retrieves a list of sites accessible to this client.
   *
   * Used by Mollom resellers only.
   *
   * @return array
   *   An array containing site resources, as returned by Mollom::getsite().
   */
  public function getSites();

  /**
   * Retrieves information about a site.
   *
   * @param string $publicKey
   *   (optional) The public Mollom API key of the site to retrieve. Defaults to
   *   the public key of the client.
   *
   * @return mixed
   *   On success, an associative array containing:
   *   - publicKey: The public Mollom API key of the site.
   *   - privateKey: The private Mollom API key of the site.
   *   - url: The URL of the site.
   *   - email: The e-mail address of the primary contact of the site.
   *   - platformName: (optional) The name of the platform running the site
   *     (e.g., "Drupal").
   *   - platformVersion: (optional) The version of the platform running the
   *     site (e.g., "6.20").
   *   - clientName: (optional) The name of the Mollom client plugin used
   *     (e.g., "Mollom").
   *   - clientVersion: (optional) The version of the Mollom client plugin used
   *     (e.g., "6.15").
   *   - expectedLanguages: (optional) An array of language ISO codes, content
   *     is expected to be submitted in on the site.
   *   On failure, the error response code returned by the server.
   */
  public function getSite($pubicKey = NULL);

  /**
   * Creates a new site.
   *
   * @param array $data
   *   An associative array of properties for the new site. At least 'url' and
   *   'email' are required. See Mollom::getSite() for details.
   *
   * @return mixed
   *   On success, the full site information of the created site; see
   *   Mollom::getSite() for details. On failure, the error response code
   *   returned by the server. Or FALSE if 'url' or 'email' was not specified.
   */
  public function createSite(array $data = array());

  /**
   * Updates a site.
   *
   * Note that most Mollom clients want to use Mollom::verifyKeys() only. This
   * method is primarily used by Mollom resellers, who are provisioning sites
   * and may need to set other site properties.
   *
   * @param array $data
   *   (optional) An associative array of properties to set for the site. See
   *   Mollom::getSite() for details.
   * @param string $publicKey
   *   (optional) The public Mollom API key of the site to update. Defaults to
   *   the public key of the client.
   *
   * @return mixed
   *   On success, the full site information of the created site; see
   *   Mollom::getSite() for details. On failure, the error response code
   *   returned by the server.
   */
  public function updateSite(array $data = array(), $publicKey = NULL);

  /**
   * Updates a site to verify API keys and send client information.
   *
   * Mollom API keys are validated in all API calls already. This method should
   * be used when the API keys of a Mollom client are configured for a site. It
   * should be invoked at least once for a site, to send client and version
   * information to Mollom in order to aid with Mollom support requests.
   *
   * @return mixed
   *   TRUE on success. On failure, the error response code returned by the
   *   server; either Mollom::REQUEST_ERROR, Mollom::AUTH_ERROR or
   *   Mollom::NETWORK_ERROR.
   */
  public function verifyKeys();

  /**
   * Deletes a site.
   *
   * @param string $publicKey
   *   The public Mollom API key of the site to delete.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function deleteSite($publicKey);

  /**
   * Checks user-submitted content with Mollom.
   *
   * @param array $data
   *   An associative array containing any of the keys:
   *   - id: The existing content ID of the content, if it or a variant or
   *     revision of it has been checked before.
   *   - postTitle: The title of the content.
   *   - postBody: The body of the content. If the content consists of multiple
   *     fields, concatenate them into one postBody string, separated by " \n"
   *     (space and line-feed).
   *   - authorName: The (real) name of the content author.
   *   - authorUrl: The homepage/website URL of the content author.
   *   - authorMail: The e-mail address of the content author.
   *   - authorIp: The IP address of the content author.
   *   - authorId: The local user ID on the client site of the content author.
   *   - authorOpenid: An indexed array of Open IDs of the content author.
   *   - checks: An indexed array of strings denoting the checks to perform, one
   *     or more of: 'spam', 'quality', 'profanity', 'language', 'sentiment'.
   *     Defaults to 'spam'.
   *   - type: An optional string identifier to request a special content
   *     classification behavior. Possible values are:
   *     - 'user': Enables classification of 'author*' request parameters as
   *       primary content. postTitle and postBody may be left empty without
   *       negative impact on the classification result. Use this for checking
   *       user registration forms. Optionally pass additional user profile text
   *       fields as postBody.
   *   - unsure: Integer denoting whether a "unsure" response should be allowed
   *     (1) for the 'spam' check (which should lead to CAPTCHA) or not (0).
   *     Defaults to 1.
   *   - strictness: A string denoting the strictness of Mollom checks to
   *     perform; one of 'strict', 'normal', or 'relaxed'. Defaults to 'normal'.
   *   - rateLimit: Seconds that must have passed by for the same author to post
   *     again. Defaults to 15.
   *   - honeypot: The value of a client-side honeypot form element, if
   *     non-empty.
   *   - url: The absolute URL to the stored content.
   *   - contextUrl: An absolute URL to parent/context content of the stored
   *     content; e.g., the URL of the article or forum thread a comment is
   *     posted on (not the parent comment that was replied to).
   *   - contextTitle: The title of the parent/context content of the stored
   *     content; e.g., the title of the article or forum thread a comment is
   *     posted on (not the parent comment that was replied to).
   *   - trackingImageId: An optional string identifier used to request an
   *     image beacon.  This is used for form behavior analysis.
   *
   * @return mixed
   *   On success, an associative array representing the full content record,
   *   containing the additional keys:
   *   - spamScore: A floating point value with a precision of 2, ranging
   *     between 0.00 and 1.00; whereas 0.00 denotes 100% spam, 0.50 denotes
   *     "unsure", and 1.00 denotes ham. Only returned if 'spam' was passed for
   *     'checks'.
   *   - spamClassification: The final spam classification; one of 'spam',
   *     'unsure', or 'ham'. Only returned if 'spam' was passed for 'checks'.
   *   - profanityScore: A floating point value with a precision of 2, ranging
   *     between 0.00 and 1.00; whereas 0.00 denotes 0% profanity and 1.00
   *     denotes 100% profanity. Only returned if 'profanity' was passed for
   *     'checks'.
   *   - qualityScore: A floating point value with a precision of 2, ranging
   *     between 0.00 and 1.00; whereas 0.00 denotes poor quality and 1.00
   *     high quality. Only returned if 'quality' was passed for 'checks'.
   *   - sentimentScore: A floating point value with a precision of 2, ranging
   *     between 0.00 and 1.00; whereas 0.00 denotes bad sentiment and 1.00
   *     good sentiment. Only returned if 'sentiment' was passed for 'checks'.
   *   - reason: A string denoting the reason for Mollom's classification; e.g.,
   *     - rateLimit: Author was seen on Mollom-protected sites within the given
   *       'rateLimit' time-frame.
   *   On failure, the error response code returned by the server.
   */
  public function checkContent(array $data = array());

  /**
   * Retrieves a CAPTCHA resource from Mollom.
   *
   * @param array $data
   *   An associative array containing:
   *   - type: A string denoting the type of CAPTCHA to create; one of 'image'
   *     or 'audio'.
   *   and any of the keys:
   *   - contentId: The ID of a content resource to link the CAPTCHA to. Allows
   *     Mollom to learn when it was unsure.
   *   - ssl: An integer denoting whether to create a CAPTCHA URL using HTTPS
   *     (1) or not (0). Only available for paid subscriptions.
   *
   * @return mixed
   *   On success, an associative array representing the full CAPTCHA record,
   *   containing:
   *   - id: The ID of the CAPTCHA.
   *   - url: The URL of the CAPTCHA.
   *   On failure, the error response code returned by the server.
   *   Or FALSE if a unknown 'type' was specified.
   */
  public function createCaptcha(array $data = array());

  /**
   * Checks whether a user-submitted solution for a CAPTCHA is correct.
   *
   * @param array $data
   *   An associative array containing:
   *   - id: The ID of the CAPTCHA to check.
   *   - solution: The answer provided by the author.
   *   and any of the keys:
   *   - authorName: The (real) name of the content author.
   *   - authorUrl: The homepage/website URL of the content author.
   *   - authorMail: The e-mail address of the content author.
   *   - authorIp: The IP address of the content author.
   *   - authorId: The local user ID on the client site of the content author.
   *   - authorOpenid: An indexed array of Open IDs of the content author.
   *   - rateLimit: Seconds that must have passed by for the same author to post
   *     again. Defaults to 15.
   *   - honeypot: The value of a client-side honeypot form element, if
   *     non-empty.
   *
   * @return mixed
   *   On success, an associative array representing the full CAPTCHA record,
   *   additionally containing:
   *   - solved: Whether the provided solution was correct (1) or not (0).
   *   - reason: A string denoting the reason for Mollom's classification; e.g.,
   *     - rateLimit: Author was seen on Mollom-protected sites within the given
   *       'rateLimit' time-frame.
   *   On failure, the error response code returned by the server.
   *   Or FALSE if no 'id' was specified.
   */
  public function checkCaptcha(array $data = array());

  /**
   * Sends feedback to Mollom.
   *
   * @param array $data
   *   An associative array containing:
   *   - reason: A string denoting the reason for why the content associated
   *     with either contentId or captchaId is being reported; one of:
   *     - spam: The content is spam, unsolicited advertising.
   *     - profanity: The content contains obscene, violent, profane language.
   *     - quality: The content is of low quality.
   *     - unwanted: The content is unwanted, taunting, off-topic.
   *   - type (optional): A string denoting the type of feedback submitted.
   *     - moderate: Feedback from the admin moderation process (default).
   *     - flag: feedback from end users flagging content as inappropriate
   *   - source (optional): A string denoting the user-interface source of the feedback.
   *   and at least one of:
   *   - contentId: A Mollom content ID associated with the content.
   *   - captchaId: A Mollom CAPTCHA ID associated with the content.
   *
   * @return bool
   *   TRUE if the feedback was sent successfully, FALSE otherwise.
   */
  public function sendFeedback(array $data);

  /**
   * Retrieves the blacklist for a site.
   *
   * @param string $publicKey
   *   (optional) The public Mollom API key of the site to retrieve the
   *   blacklist for. Defaults to the public key of the client.
   *
   * @return mixed
   *   An array containing blacklist entries; see Mollom::getBlacklistEntry()
   *   for details. On failure, the error response code returned by the server.
   */
  public function getBlacklist($publicKey = NULL);

  /**
   * Retrieves a blacklist entry stored for a site.
   *
   * @param string $entryId
   *   The ID of the blacklist entry to retrieve.
   * @param string $publicKey
   *   (optional) The public Mollom API key of the site to retrieve the
   *   blacklist entry for. Defaults to the public key of the client.
   *
   * @return mixed
   *   On success, an associative array containing:
   *   - id: The ID the of blacklist entry.
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
   *   On failure, the error response code returned by the server.
   */
  public function getBlacklistEntry($entryId, $publicKey = NULL);

  /**
   * Creates or updates a blacklist entry for a site.
   *
   * @param array $data
   *   An associative array describing the blacklist entry to create or update.
   *   See return value of Mollom::getBlacklistEntry() for details. To update
   *   an existing entry, its ID must be specified in 'id'.
   * @param string $publicKey
   *   (optional) The public Mollom API key of the site to save the blacklist
   *   entry for. Defaults to the public key of the client.
   *
   * @return mixed
   *   On success, the full blacklist entry record of the saved entry; see
   *   Mollom::getBlacklistEntry() for details. On failure, the error response
   *   code returned by the server.
   */
  public function saveBlacklistEntry(array $data = array(), $publicKey = NULL);

  /**
   * Deletes a blacklist entry from a site.
   *
   * @param string $entryId
   *   The ID of the blacklist entry to delete.
   * @param string $publicKey
   *   (optional) The public Mollom API key of the site to create the blacklist
   *   entry for. Defaults to the public key of the client.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function deleteBlacklistEntry($entiryId, $publicKey = NULL);

  /**
   * Generates a URL to a tracking image.
   *
   * This image can be used by form behavior analysis to analyze the
   * humanity of the content author.
   *
   * @return array
   *   An associative array of tracking information generated.
   *   - tracking_url: the url to the tracking image (without any http protocol)
   *   - tracking_id: the tracking id generated
   */
  public function getTrackingImage();

  /**
   * Convert array of expected languages to a string for storage.
   *
   * @param array $languages
   *   An array of ISO-639-1 language codes
   * @return string
   *   A flattened string.
   */
  public static function flattenExpectedLanguages(array $languages);
}
