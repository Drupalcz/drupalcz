<?php

namespace Drupal\mollom\Tests;
use Drupal\mollom\Entity\FormInterface;

/**
 * Tests node form protection.
 * @group mollom
 */
class NodeFormTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

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

    // @todo 'view own unpublished content' permission required to prevent a
    //   bogus access denied watchdog caused by a bug in Drupal core.
    // @see http://drupal.org/node/1429442
    $this->webUser = $this->drupalCreateUser(['create article content', 'view own unpublished content']);
  }

  /**
   * Tests saving of Mollom data for protected node forms.
   *
   * node_form() uses a button-level form submit handler, which invokes
   * form-level submit handlers before a new node entity has been stored.
   * Therefore, the submitted form values do not contain a 'nid' yet, so Mollom
   * session data cannot be stored for the new node.
   */
  function testData() {
    // Enable Mollom CAPTCHA protection for Article nodes.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('node_article_form', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->drupalLogout();

    // Login and submit a node.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/article');
    $captchaId = $this->assertResponseIDInForm('captchaId');
    $edit = [
      'title[0][value]' => 'spam',
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertUrl('node/' . $this->node->id());
    $this->assertMollomData('node', $this->node->id(), 'captchaId', $captchaId);
  }

  /**
   * Tests retaining of node form submissions containing profanity.
   */
  function testRetain() {
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('node_article_form', FormInterface::MOLLOM_MODE_ANALYSIS, NULL, [
      'checks[profanity]' => TRUE,
      'discard' => 0,
    ]);
    $this->drupalLogout();

    // Login and submit a node.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/article');
    $edit = [
      'title[0][value]' => 'profanity',
      'body[0][value]' => 'ham profanity',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    debug($this->node, 'node');
    $this->assertFalse($this->node->isPublished(), t('Node containing profanity was retained as unpublished.'));
    $this->assertUrl('node/' . $this->node->id());
    $this->assertMollomData('node', $this->node->id());
  }
}
