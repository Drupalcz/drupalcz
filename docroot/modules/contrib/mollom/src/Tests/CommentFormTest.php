<?php

namespace Drupal\mollom\Tests;
use Drupal\mollom\Entity\FormInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Check that the comment submission form can be protected.
 * @group mollom
 */
class CommentFormTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  const COMMENT_TEST_TYPE = 'test_type';

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $this->webUser = $this->drupalCreateUser(['create article content', 'access comments', 'post comments', 'skip comment approval']);
    $this->node = $this->drupalCreateNode(['type' => 'article', 'uid' => $this->webUser->uid]);

    $this->addCommentsToNode('article');
  }

  /**
   * Make sure that the comment submission form can be unprotected.
   */
  function testUnprotectedCommentForm() {
    // Request the comment reply form. There should be no CAPTCHA.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertNoCaptchaField();
    $this->assertNoPrivacyLink();

    // Preview a comment that is 'spam' and make sure there is still no CAPTCHA.
    $this->drupalPostForm(NULL, ['comment_body[0][value]' => 'spam'], t('Preview'));
    $this->assertNoCaptchaField();
    $this->assertNoPrivacyLink();

    // Save the comment and make sure it appears.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertRaw('<p>spam</p>', t('A comment that is known to be spam appears on the screen after it is submitted.'));
  }

  /**
   * Make sure that the comment submission form can be protected by captcha only.
   */
  function testCaptchaProtectedCommentForm() {
    // Test the basic comment form.
    $this->verifyCaptcha('comment_comment_form', $this->node);
  }

  /**
   * Test alternate comment type captcha submissions.
   */
  function testAlternateCaptchaProtectedCommentForm() {
    $page = $this->setupAlternateComments(self::COMMENT_TEST_TYPE, 'page');
    $this->verifyCaptcha('comment_test_type_form', $page);
  }

  /**
   * Make sure that the comment submission form can be fully protected.
   */
  function testTextAnalysisProtectedCommentForm() {
    // Test the basic comment form.
    $this->verifyCommentAnalysis('comment_comment_form', $this->node);
  }

  /**
   * Make sure that alternate comment type form can be fully protected.
   */
  function testAlternateTextAnalysisProtectedCommentForm() {
    $page = $this->setupAlternateComments(self::COMMENT_TEST_TYPE, 'page');
    $this->verifyCommentAnalysis('comment_test_type_form', $page);
  }

  /**
   * Helper function to set up a new comment type and add it to a node.
   *
   * @param string $comment_type_id
   *   The id to use for the new comment type.
   * @param string bundle
   *   A node bundle to create.
   * @returns \Drupal\node\NodeInterface
   *   A new node of the specified bundle with the comment type field.
   */
  protected function setupAlternateComments($comment_type_id, $bundle) {
    $this->createCommentType($comment_type_id);
    $this->drupalCreateContentType(['type' => $bundle, 'name' => 'Page']);
    $this->addCommentsToNode($bundle, DRUPAL_OPTIONAL, $comment_type_id);
    return $this->drupalCreateNode(['type' => $bundle, 'uid' => $this->webUser->id()]);
  }

  /**
   * Tests textual analysis on a comment form.
   *
   * @param string $form_id
   *   The form id of the comment form to protect.
   * @param \Drupal\node\NodeInterface $node
   *   A node that has the protected comment type as a form field.
   */
  protected function verifyCommentAnalysis($form_id, NodeInterface $node) {
    // Enable Mollom text-classification for comments.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI($form_id);
    $this->drupalLogout();

    // Request the comment reply form.  Initially, there should be no CAPTCHA.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/'. $node->id());
    $this->assertNoCaptchaField();
    $this->assertPrivacyLink();

    // Try to save a comment that is 'unsure' and make sure there is a CAPTCHA.
    $edit = [
      'comment_body[0][value]' => 'unsure',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertCaptchaField();
    $contentId = $this->assertResponseIDInForm('contentId');
    $this->assertPrivacyLink();

    // Try to submit the form by solving the CAPTCHA incorrectly. At this point,
    // the submission should be blocked and a new CAPTCHA generated, but only if
    // the comment is still neither ham or spam.
    $this->postIncorrectCaptcha(NULL, array(), t('Save'));
    $this->assertCaptchaField();
    $captchaId = $this->assertResponseIDInForm('captchaId');
    $this->assertPrivacyLink();

    // Correctly solving the CAPTCHA should accept the form submission.
    $this->postCorrectCaptcha(NULL, array(), t('Save'));
    $this->assertRaw('<p>' . $edit['comment_body[0][value]'] . '</p>', t('A comment that may contain spam was found.'));
    $cids = $this->loadCommentsBySubject($edit['comment_body[0][value]']);
    $this->assertMollomData('comment', reset($cids), 'contentId', $contentId);

    // Try to save a new 'spam' comment; it should be discarded, with no CAPTCHA
    // appearing on the page.
    $this->resetResponseID();
    $this->drupalGet('node/' . $node->id());
    $this->assertPrivacyLink();
    $original_number_of_comments = $this->getCommentCount($node->id());
    $this->assertSpamSubmit(NULL, array('comment_body[0][value]'), array(), t('Save'));
    $contentId = $this->assertResponseIDInForm('contentId');
    $this->assertCommentCount($node->id(), $original_number_of_comments);
    $this->assertPrivacyLink();

    // Try to save again; it should be discarded, with no CAPTCHA.
    $this->assertSpamSubmit(NULL, array('comment_body[0][value]'), array(), t('Save'));
    $contentId = $this->assertResponseIDInForm('contentId');
    $this->assertCommentCount($node->id(), $original_number_of_comments);
    $this->assertPrivacyLink();

    // Save a new 'ham' comment.
    $this->resetResponseID();
    $this->drupalGet('node/' . $node->id());
    $this->assertPrivacyLink();
    $original_number_of_comments = $this->getCommentCount($node->id());
    $this->assertHamSubmit(NULL, array('comment_body[0][value]'), array(), t('Save'));
    $this->assertRaw('<p>ham</p>', t('A comment that is known to be ham appears on the screen after it is submitted.'));
    $this->assertCommentCount($node->id(), $original_number_of_comments + 1);
    $cids = $this->loadCommentsBySubject('ham');
    $this->assertMollomData('comment', reset($cids));
  }

  /**
   * Make sure that the comment submission form can be protected by captcha only.
   *
   * @param string $form_id
   *   The id of the comment form to verify
   * @param \Drupal\node\NodeInterface $node
   *   A sample node to with the comment type added as a field.
   */
  function verifyCaptcha($form_id, NodeInterface $node) {
    // Enable Mollom CAPTCHA protection for comments.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI($form_id, FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->drupalLogout();

    // Request the comment reply form. There should be a CAPTCHA form.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertCaptchaField();
    $this->assertResponseIDInForm('captchaId');
    $this->assertNoPrivacyLink();

    // Try to submit an incorrect answer for the CAPTCHA, without value for
    // required field.
    $this->postIncorrectCaptcha(NULL, [], t('Preview'));
    $this->assertText(t('Comment field is required.'));
    $this->assertResponseIDInForm('captchaId', TRUE);
    $this->assertNoPrivacyLink();

    // Try to submit a correct answer for the CAPTCHA, still without required
    // field value.
    $this->postCorrectCaptcha(NULL, [], t('Preview'));
    $this->assertText(t('Comment field is required.'));
    $captchaId = $this->assertResponseIDInForm('captchaId', TRUE);
    $this->assertNoPrivacyLink();

    // Finally, we should be able to submit a comment.
    $this->drupalPostForm(NULL, array('comment_body[0][value]' => 'spam'), t('Save'));
    $this->assertText(t('Your comment has been posted.'));
    $this->assertRaw('<p>spam</p>', t('Spam comment could be posted with correct CAPTCHA.'));
    $cids = $this->loadCommentsBySubject('spam');
    $this->assertMollomData('comment', reset($cids), 'captchaId', $captchaId);

    // Verify we can solve the CAPTCHA directly.
    $this->resetResponseID();
    $value = 'some more spam';
    $this->drupalGet('node/' . $node->id());
    $this->assertCaptchaField();
    $captchaId = $this->assertResponseIDInForm('captchaId');
    $edit = [
      'comment_body[0][value]' => $value,
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your comment has been posted.'));
    $cids = $this->loadCommentsBySubject($value);
    $this->assertMollomData('comment', reset($cids), 'captchaId', $captchaId);
  }

  /**
   * Return the number of comments for a node of the given node ID.  We
   * can't use comment_num_all() here, because that is statically cached
   * and therefore will not work correctly with the SimpleTest browser.
   */
  private function getCommentCount($nid) {
    // The field name for the comment field might be "comment" or "test_type"
    // depending on the test.
    $entity_manager = \Drupal::entityManager();
    $node = Node::load($nid);
    if (array_key_exists(self::COMMENT_TEST_TYPE, $entity_manager->getFieldDefinitions($node->getEntityTypeId(), $node->bundle()))) {
      $field_name = self::COMMENT_TEST_TYPE;
    }
    else {
      $field_name = 'comment';
    }
    return \Drupal::database()->query('SELECT comment_count FROM {comment_entity_statistics} WHERE entity_id = :nid and entity_type=:type and field_name=:field',
      [
        ':nid' => $nid,
        ':type' => 'node',
        ':field' => $field_name,
      ]
    )->fetchField();
  }

  /**
   * Test that the number of comments for a node matches an expected value.
   *
   * @param $nid
   *   A node ID
   * @param $expected
   *   An integer with the expected number of comments for the node.
   * @param $message
   *   An optional string with the message to be used in the assertion.
   */
  protected function assertCommentCount($nid, $expected, $message = '') {
    $actual = $this->getCommentCount($nid);
    if (!$message) {
      $message = t('Node @nid has @actual comment(s), expected @expected.', array('@nid' => $nid, '@actual' => $actual, '@expected' => $expected));
    }
    $this->assertEqual($actual, $expected, $message);
  }
}

