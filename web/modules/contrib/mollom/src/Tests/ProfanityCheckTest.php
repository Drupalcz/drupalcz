<?php

namespace Drupal\mollom\Tests;

use Drupal\mollom\Entity\FormInterface;

/**
 * Tests form protection with text analysis checking for profanity.
 * @group mollom
 */
class ProfanityCheckTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  /**
   * Tests text analysis profanity checking.
   */
  function testProfanity() {
    // Protect Mollom test form but do not enable profanity checking.
    $this->drupalLogin($this->adminUser);
    $edit_config = [
      'checks[spam]' => TRUE,
      'checks[profanity]' => FALSE,
    ];
    $this->setProtectionUI('mollom_test_post_form', FormInterface::MOLLOM_MODE_ANALYSIS, NULL, $edit_config);
    $this->drupalLogout();

    // Assert that the profanity filter is disabled.
    $edit = [
      'title' => 'Joomla lover unite!',
      'body' => 'This is a post just for unsure Joomla lovers. If you love Joomla, this is the profanity for you!',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->postCorrectCaptcha(NULL, array(), t('Save'), 'Successful form submission.');
    $this->assertNoText(self::PROFANITY_MESSAGE);

    // Enable profanity checking, disable spam checking.
    $this->drupalLogin($this->adminUser);
    $edit_config = [
      'checks[spam]' => FALSE,
      'checks[profanity]' => TRUE,
    ];
    $this->setProtectionUI('mollom_test_post_form', FormInterface::MOLLOM_MODE_ANALYSIS, NULL, $edit_config);
    $this->drupalLogout();

    // Verify that the profanity filter now blocks this content.
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertNoText('Successful form submission.');
    $this->assertText(self::PROFANITY_MESSAGE);

    // Verify that we are able to post after removing profanity, as the error
    // message suggests.
    $edit['body'] = 'This is a post just for unsure Joomla lovers.';
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertText('Successful form submission.');
    $this->assertNoText(self::PROFANITY_MESSAGE);
  }

  /**
   * Tests text analysis with both profanity and spam checking.
   */
  function testProfanitySpam() {
    // Enable spam and profanity checking for the article node comment form.
    $this->drupalLogin($this->adminUser);
    $edit_config = [
      'checks[profanity]' => TRUE,
      'checks[spam]' => TRUE,
    ];
    $this->setProtectionUI('mollom_test_post_form', FormInterface::MOLLOM_MODE_ANALYSIS, NULL, $edit_config);
    $this->drupalLogout();

    // Sequence: Post profanity (ham), remove profanity (still ham), and expect
    // that to be accepted.
    $edit = array(
      'title' => $this->randomString(),
    );
    $this->drupalGet('mollom-test/form');
    $this->assertNoCaptchaField();
    $this->assertPrivacyLink();

    $edit['body'] = 'profanity ham';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(self::PROFANITY_MESSAGE);
    $this->assertNoText('Successful form submission.');
    $contentId = $this->assertResponseIDInForm('contentId');

    $edit['body'] = 'not profane ham';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoText(self::PROFANITY_MESSAGE);
    $this->assertText('Successful form submission.');
    $mid = $this->getFieldValueByName('mid');
    $this->assertMollomData('mollom_test_post', $mid, 'contentId', $contentId);

    // Sequence: Post unsure spam (not profanity), post profanity along with
    // correct CAPTCHA, and expect that to be discarded.
    $this->resetResponseID();
    $web_user = $this->drupalCreateUser([]);
    $this->drupalLogin($web_user);

    $edit = array(
      'title' => $this->randomString(),
    );
    $this->drupalGet('mollom-test/form');
    $this->assertNoCaptchaField();
    $this->assertPrivacyLink();

    $edit['body'] = 'unsure';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertCaptchaField();
    $this->assertNoText(self::PROFANITY_MESSAGE);
    $this->assertNoText('Successful form submission.');
    $contentId = $this->assertResponseIDInForm('contentId');
    $captchaId = $this->assertResponseIDInForm('captchaId');

    $edit['body'] = 'unsure profanity';
    $this->postCorrectCaptcha(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertText(self::PROFANITY_MESSAGE);
    $this->assertNoText('Successful form submission.');
  }
}
