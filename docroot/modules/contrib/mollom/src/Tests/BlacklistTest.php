<?php

namespace Drupal\mollom\Tests;

use Drupal\component\Utility\Unicode;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\mollom\Storage\BlacklistStorage;

/**
 * Tests URL and text blacklist functionality.
 * @group mollom
 */
class BlacklistTest extends MollomTestBase {

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server'];

  public $disableDefaultSetup = TRUE;

  function setUp() {
    parent::setUp();
    $this->setKeys();
  }

  /**
   * Test the blacklist functionality at the API level without using a web interface.
   */
  function testBlacklistAPI() {
    $mollom = $this->getClient(TRUE);
    // Remove any stale blacklist entries from test runs that did not finish.
    $blacklist = $mollom->getBlacklist();
    foreach ($blacklist as $entry) {
      if (REQUEST_TIME - strtotime($entry['created']) > 86400) {
        $mollom->deleteBlacklistEntry($entry['id']);
      }
    }
    $this->assertMollomWatchdogMessages();

    // Blacklist a URL.
    $domain = Unicode::strtolower($this->randomMachineName()) . '.com';
    $entry = $mollom->saveBlacklistEntry([
      'value' => $domain,
      'context' => 'allFields',
      'reason' => 'spam',
      'match' => 'contains',
    ]);
    $this->assertMollomWatchdogMessages();
    $this->assertTrue($entry['id'], t('The URL was blacklisted.'));

    // Check whether posts containing the blacklisted URL are properly blocked.
    $result = $mollom->checkContent([
      'postBody' => "When the exact URL is present, the post should get blocked: http://{$domain}",
    ]);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Exact URL match was blocked.'));

    $result = $mollom->checkContent([
      'postBody' => "When the URL is expanded in the back, the post should get blocked: http://{$domain}/oh-my",
    ]);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Partial URL match was blocked.'));

    $result = $mollom->checkContent([
      'postBody' => "When the URL is expanded in the front, the post should get blocked: http://www.{$domain}",
    ]);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('URL with www-prefix was blocked.'));

    $result = $mollom->checkContent([
      'postBody' => "When the URL has a different schema, the post should get blocked: ftp://www.{$domain}",
    ]);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('URL with different schema was blocked.'));

    $result = $mollom->deleteBlacklistEntry($entry['id']);
    $this->assertMollomWatchdogMessages();
    $this->assertIdentical($result, TRUE, t('The blacklisted URL was removed.'));

    // Blacklist a word.
    // @todo As of now, only non-numeric, lower-case text seems to be supported.
    $term = Unicode::strtolower(preg_replace('/[^a-zA-Z]/', '', $this->randomMachineName()));
    $entry = $mollom->saveBlacklistEntry([
      'value' => $term,
      'context' => 'allFields',
      'reason' => 'spam',
      'match' => 'contains',
    ]);
    $this->assertMollomWatchdogMessages();
    $this->assertTrue($entry['id'], t('The text was blacklisted.'));

    // Check whether posts containing the blacklisted word are properly blocked.
    $data = [
      'postBody' => $term,
    ];
    $result = $mollom->checkContent($data);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Identical match was blocked.'));

    $data = [
      'postBody' => "When the term is present, the post should get blocked: " . $term,
    ];
    $result = $mollom->checkContent($data);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Exact match was blocked.'));

    $data = [
      'postBody' => "When match is 'contains', the word can be surrounded by other text: abc" . $term . "def",
    ];
    $result = $mollom->checkContent($data);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Partial match was blocked.'));

    // Update the blacklist entry to match the term only exactly.
    $entry = $mollom->saveBlacklistEntry([
      'id' => $entry['id'],
      'value' => $term,
      'context' => 'allFields',
      'reason' => 'spam',
      'match' => 'exact',
    ]);
    $this->assertMollomWatchdogMessages();
    $this->assertTrue($entry['id'], t('The blacklist entry was updated.'));

    $data = [
      'postBody' => "When match is 'exact', it has to be exact: " . $term,
    ];
    $result = $mollom->checkContent($data);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 1.0);
    $this->assertEqual($result['spamClassification'], 'spam', t('Exact match was blocked.'));

    $data = [
      'postBody' => "When match is 'exact', it has to be exact: abc{$term}def",
    ];
    $result = $mollom->checkContent($data);
    $this->assertMollomWatchdogMessages();
    $this->assertSame('spamScore', $result['spamScore'], 0.5);
    $this->assertEqual($result['spamClassification'], 'unsure', t('Partial match was not blocked.'));

    $result = $mollom->deleteBlacklistEntry($entry['id']);
    $this->assertMollomWatchdogMessages();
    $this->assertIdentical($result, TRUE, t('The blacklisted text was removed.'));

    // Try to remove a non-existing entry.
    // @todo Ensure that the ID does not exist.
    $result = $mollom->deleteBlacklistEntry(999);
    $this->assertMollomWatchdogMessages(RfcLogLevel::EMERGENCY);
    $this->assertNotIdentical($result, TRUE, t('Error response for a non-existing blacklist text found.'));
    $this->assertSame('Response code', $mollom->lastResponseCode, 404);
  }

  /**
   * Test the blacklist administration interface.
   *
   * We don't need to check whether the blacklisting actually works
   * (i.e. blocks posts) because that is tested in testTextBlacklistAPI() and
   * testURLBlacklistAPI().
   */
  function testBlacklistUI() {
    // Log in as an administrator and access the blacklist administration page.
    $this->adminUser = $this->drupalCreateUser([
      'administer mollom',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add a word to the spam blacklist.
    $this->drupalGet('admin/config/content/mollom/blacklist/add');
    $text = $this->randomMachineName();
    $edit = [
      'value' => $text,
      'context' => 'allFields',
      'match' => 'contains',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add blacklist entry'));
    $text = Unicode::strtolower($text);
    $this->assertText(t('The entry was added to the spam blacklist.'));
    $this->assertText($text);

    // Remove the word from the spam blacklist.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertText(t('There are no entries in the blacklist.'));

    // Add a word to the profanity blacklist.
    $this->drupalGet('admin/config/content/mollom/blacklist/add');
    $text = $this->randomMachineName();
    $edit = [
      'reason' => BlacklistStorage::TYPE_PROFANITY,
      'value' => $text,
      'context' => 'allFields',
      'match' => 'contains',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add blacklist entry'));
    $this->assertText(t('The entry was added to the profanity blacklist.'));
    $text = Unicode::strtolower($text);
    $this->assertText($text);

    // Remove the word from the profanity blacklist.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertText('There are no entries in the blacklist.');
  }
}
