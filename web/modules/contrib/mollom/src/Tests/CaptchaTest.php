<?php

namespace Drupal\mollom\Tests;
use Drupal\mollom\Entity\FormInterface;

/**
 * Test basic CAPTCHA functionality.
 *
 * @group mollom
 */
class CaptchaTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];
  public $disableDefaultSetup = TRUE;
  protected $useLocal = TRUE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setKeys(TRUE);
    $this->assertValidKeys();

    $this->setProtection('mollom_test_post_form', FormInterface::MOLLOM_MODE_CAPTCHA);
  }

  /**
   * Tests #required validation of CAPTCHA form element.
   */
  function testCAPTCHARequired() {
    $this->drupalGet('mollom-test/form');
    // Verify that CAPTCHA cannot be left empty.
    $edit = [
      'title' => $this->randomString(),
    ];
    $this->assertCaptchaField();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(self::INCORRECT_MESSAGE);
    $this->assertNoText(t('Successful form submission.'));

    // Verify it again on subsequent POST.
    $this->assertCaptchaField();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(self::INCORRECT_MESSAGE);
    $this->assertNoText(t('Successful form submission.'));

    // Verify that incorrect solution still leaves the field required.
    $this->postIncorrectCaptcha(NULL, $edit, t('Save'), t('Successful form submission.'));

    // Verify correct solution, but trigger other validation errors.
    $edit = [
      'title' => '',
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertNoText(t('Successful form submission.'));

    // Lastly, confirm we're able to submit.
    $edit = [
      'title' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoText(self::INCORRECT_MESSAGE);
    $this->assertTestSubmitData();
  }

  /**
   * Tests incorrect solution of CAPTCHA form element.
   */
  function testCAPTCHAIncorrect() {
    $this->drupalGet('mollom-test/form');

    // Verify that incorrect solution still leaves the field required.
    $edit = [
      'title' => $this->randomString(),
    ];
    $this->postIncorrectCaptcha(NULL, $edit, t('Save'), t('Successful form submission.'));

    // Lastly, verify correct solution.
    $edit = array(
      self::CAPTCHA_INPUT => 'correct',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertTestSubmitData();
  }

  /**
   * Tests correct solution of CAPTCHA.
   */
  function testCAPTCHACorrect() {
    $this->drupalGet('mollom-test/form');

    $edit = [
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertNoText(t('Successful form submission.'));

    $edit = [
      'body' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertNoText(t('Successful form submission.'));

    $edit = [
      'title' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertTestSubmitData();
  }

  /**
   * Tests correct solution of CAPTCHA in a single pass.
   */
  function testCAPTCHACorrectSinglePass() {
    $this->drupalGet('mollom-test/form');

    // Verify that CAPTCHA can be solved in one shot.
    $edit = [
      'title' => $this->randomString(),
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertTestSubmitData();
  }

  /**
   * Tests the CAPTCHA type switch callback.
   */
  function testCAPTCHASwitchCallback() {
    // Verify that the CAPTCHA can be switched on a CAPTCHA-only protected form.
    // (without having a contentId)
    $this->drupalGet('mollom-test/form');
    $this->drupalPostAjaxForm(NULL, [], ['mollom_switch_captcha' => t('Switch to audio verification')]);
    $this->assertText(t('Enter only the first letter of each word you hear'), 'Audio instructions displayed.');
  }

  /**
   * Tests the CAPTCHA audio enable/disable functionality.
   */
  function testCAPTCHAAudioEnable() {
    // Default should be enabled audio.
    $this->drupalGet('mollom-test/form');
    $this->assertFieldByName('mollom_switch_captcha');

    // Verify that CAPTCHA cannot be switched when audio is disabled.
    $config = \Drupal::configFactory()->getEditable('mollom.settings');
    $config->set('captcha.audio.enabled', FALSE)->save();
    $this->drupalGet('mollom-test/form');
    $this->assertNoFieldByName('mollom_switch_captcha');
  }
}
