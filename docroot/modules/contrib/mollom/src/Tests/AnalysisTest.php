<?php

namespace Drupal\mollom\Tests;

use Drupal\mollom\Entity\FormInterface;

/**
 * Tests text analysis functionality.
 * @group mollom
 */
class AnalysisTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  public $disableDefaultSetup = TRUE;
  protected $useLocal = TRUE;

  public function setUp() {
    parent::setUp();
    $this->setKeys(TRUE);
    $this->assertValidKeys();

    $this->setProtection('mollom_test_post_form', FormInterface::MOLLOM_MODE_ANALYSIS);
  }

  /**
   * Tests basic unsure submission flow.
   */
  public function testUnsureCorrect() {
    $this->drupalGet('mollom-test/form');
    $this->assertNoCaptchaField();
    $edit = [
      'title' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId', TRUE);
    $this->assertResponseIDInForm('captchaId', TRUE);
    $this->assertCaptchaField();

    $edit = [
      'title' => 'unsure unsure',
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertTestSubmitData();
  }

  /**
   * Tests unsure post with repetitive incorrect CAPTCHA solution.
   *
   * @todo CAPTCHA ID should stay the same.
   * @see http://drupal.org/node/1959904
   */
  function testUnsureIncorrect() {
    $this->drupalGet('mollom-test/form');
    $this->assertNoCaptchaField();
    $edit = [
      'title' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId', TRUE);
    $this->assertResponseIDInForm('captchaId', TRUE);
    $this->assertCaptchaField();

    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertResponseIDInForm('contentId');
//    $this->assertResponseIDInForm('captchaId');
    $this->assertCaptchaField();
    $edit = [
      self::CAPTCHA_INPUT => 'incorrect',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId');
//    $this->assertResponseIDInForm('captchaId');
    $this->assertCaptchaField();

    $edit = [
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertTestSubmitData();
  }

  /**
   * Tests unsure post with other validation errors.
   */
  function testUnsureValidation() {
    // The 'title' field is required. Omit its value to verify that the CAPTCHA
    // can be solved, repetitive form validations do not show a CAPTCHA again,
    // and the post can finally be submitted by providing a title.
    $this->drupalGet('mollom-test/form');
    $this->assertNoCaptchaField();
    $edit = [
      'body' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId', TRUE);
    $this->assertResponseIDInForm('captchaId', TRUE);
    $this->assertCaptchaField();
    $edit = [
      'body' => 'unsure unsure',
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId');
    $this->assertNoCaptchaField();
    $edit = [
      'body' => 'unsure unsure unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId');
    $this->assertNoCaptchaField();
    $edit = [
      'title' => 'unsure',
      'body' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertTestSubmitData();
  }

  /**
   * Tests unsure posts turning into ham.
   */
  function testUnsureHam() {
    $this->drupalGet('mollom-test/form');
    $this->assertNoCaptchaField();
    // Posting unsure as title would submit the form, so post as body instead.
    $edit = [
      'body' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId', TRUE);
    $this->assertResponseIDInForm('captchaId', TRUE);
    $this->assertCaptchaField();
    // Turn the post into ham.
    $edit = [
      'body' => 'ham',
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId');
    $this->assertNoCaptchaField();
    // Turn the post back into unsure.
    $edit = [
      'body' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId');
    $this->assertNoCaptchaField();

    $edit = [
      'title' => 'irrelevant',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertTestSubmitData();
  }

  /**
   * Tests unsure posts turning into spam.
   *
   * @todo CAPTCHA ID should stay the same.
   * @see http://drupal.org/node/1959904
   */
  function testUnsureSpam() {
    $this->drupalGet('mollom-test/form');
    $this->assertNoCaptchaField();
    $edit = [
      'body' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId', TRUE);
    $this->assertResponseIDInForm('captchaId', TRUE);
    $this->assertCaptchaField();

    $edit = [
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId');
    $this->assertNoCaptchaField();

    // Turn the post into spam.
    $edit = [
      'title' => 'spam',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponseIDInForm('contentId');
    $this->assertNoCaptchaField();
    $this->assertText(self::SPAM_MESSAGE);
    $this->assertNoText('Successful form submission.');

    // Turn the post back into unsure.
    $edit = array(
      'title' => 'unsure',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertNoCaptchaField();
    $this->assertTestSubmitData();
  }
}

