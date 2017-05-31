<?php

namespace Drupal\mollom\Tests;

/**
 * Tests that users having higher privileges can bypass Mollom protection.
 * @group mollom
 */
class BypassAccessTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  /**
   * Tests 'bypass access' property of registered forms.
   */
  function testBypassAccess() {
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('mollom_test_post_form');
    $this->drupalLogout();

    // Create a regular user and submit form.
    $web_user = $this->drupalCreateUser([]);
    $this->drupalLogin($web_user);
    $edit = [
      'title' => 'ham'
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertTestSubmitData();

    // Ensure a user having one of the permissions to bypass access can post
    // spam without triggering the spam protection.
    $this->drupalLogin($this->adminUser);

    $edit = [
      'title' => 'spam',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertNoText(self::SPAM_MESSAGE);
    $this->assertTestSubmitData();

    // Log in back the regular user and try to submit spam.
    $this->drupalLogin($web_user);
    $this->drupalGet('mollom-test/form');

    $this->drupalPostForm(NULL, ['title' => 'spam'], t('Save'));
    $this->assertText(self::SPAM_MESSAGE);
  }
}

