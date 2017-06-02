<?php

namespace Drupal\mollom\Tests;
use Drupal\Core\Session\AccountInterface;

/**
 * Verify that session data is properly stored and content can be reported to Mollom.
 * @group mollom
 */
class ReportingTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test', 'views'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    $this->addCommentsToNode();

    $this->webUser = $this->drupalCreateUser(['create article content', 'access comments', 'post comments', 'skip comment approval']);
  }

  /**
   * Tests reporting comments.
   */
  function testReportComment() {
    $comment_storage = \Drupal::entityManager()->getStorage('comment');
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('comment_comment_form');
    $this->drupalLogout();

    $this->node = $this->drupalCreateNode(['type' => 'article']);

    // Post a comment.
    $this->drupalLogin($this->webUser);
    $edit = array(
      'comment_body[0][value]' => 'ham',
    );
    $this->drupalPostForm('node/' . $this->node->id(), $edit, t('Save'));
    $comments = $this->loadCommentsBySubject($edit['comment_body[0][value]']);
    $comment = $comment_storage->load(reset($comments));
    $this->assertTrue($comment, t('Comment was found in the database.'));
    $this->assertMollomData('comment', $comment->id());

    // Log in comment administrator and verify that we can report to Mollom.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $this->node->id());
    $this->assertText($edit['comment_body[0][value]'], t('Comment found.'));
    $this->clickLink(t('Delete'));
    $edit = array(
      'mollom[feedback]' => 'spam',
    );
    $this->drupalPostForm(NULL, $edit, t('Delete'));
    $this->assertText(t('The comment and all its replies have been deleted.'));
    $this->assertText(t('The content was successfully reported as inappropriate.'));

    // Verify that the comment and Mollom session data has been deleted.
    $comment_storage->resetCache();
    $this->assertTrue(empty($comment_storage->load($comment->id())), t('Comment was deleted.'));
    $this->assertNoMollomData('comment', $comment->id());
  }

  /**
   * Tests mass-reporting comments.
   */
  function testMassReportComments() {
    $comments = [];
    $comment_storage = \Drupal::entityManager()->getStorage('comment');
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('comment_comment_form');
    $this->drupalLogout();

    $this->node = $this->drupalCreateNode(array('type' => 'article'));
    $this->addCommentsToNode();

    // Post 3 comments.
    $this->drupalLogin($this->webUser);
    foreach (range(1, 3) as $num) {
      $edit = array(
        'subject[0][value]' => $this->randomMachineName(),
        'comment_body[0][value]' => 'ham',
      );
      $this->drupalPostForm('node/' . $this->node->id(), $edit, t('Save'));
      $cids = $this->loadCommentsBySubject($edit['subject[0][value]']);
      $comments[$num] = $comment_storage->load(reset($cids));
      $this->assertTrue(!empty($comments[$num]), t('Comment was found in the database.'));
      $this->assertMollomData('comment', $comments[$num]->id());
    }

    // Log in comment administrator and verify that we can mass-report all
    // comments to Mollom.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content/comment');
    $edit = array(
      'operation' => 'delete',
    );
    /* @var $comment \Drupal\comment\Entity\Comment */
    foreach ($comments as $comment) {
      $this->assertText($comment->getSubject(), t('Comment found.'));
      $edit["comments[{$comment->id()}]"] = TRUE;
    }
    $this->drupalPostForm(NULL, $edit, t('Update'));
    /* @var $comment \Drupal\comment\Entity\Comment */
    foreach ($comments as $comment) {
      $this->assertText($comment->getSubject(), t('Comment found.'));
    }
    $edit = array(
      'mollom[feedback]' => 'spam',
    );
    $this->drupalPostForm(NULL, $edit, t('Delete comments'));
    $this->assertText(t('Deleted @count comments.', array('@count' => count($comments))));
    $this->assertText(t('The posts were successfully reported as inappropriate.'));

    // Verify that the comments and Mollom session data has been deleted.
    $comment_storage->resetCache();
    foreach ($comments as $comment) {
      $this->assertTrue(empty($comment_storage->load($comment->id())), t('Comment was deleted.'));
      $this->assertNoMollomData('comment', $comment->id());
    }
  }

  /**
   * Tests appearance of feedback options on node delete forms.
   */
  function testReportNode() {
    // Create a second node type, which is not protected.
    $this->drupalCreateContentType(array('type' => 'unprotected', 'name' => 'Unprotected'));
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, [
      'create unprotected content',
      'delete own unprotected content',
      'delete own article content',
    ]);

    // Protect the article node type.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('node_article_form');
    $this->drupalLogout();

    // Login and submit a protected article node.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/article');
    $edit = array(
      'title[0][value]' => 'protected ham',
      'body[0][value]' => 'ham',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertUrl('node/' . $this->node->id());
    $this->assertMollomData('node', $this->node->id());

    // Verify that no feedback options appear on the delete confirmation form
    // for the node author.
    $this->drupalGet('node/' . $this->node->id() . '/delete');
    $this->assertResponse(200);
    $this->assertNoText(t('Report as…'));

    // Verify that feedback options appear for the admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $this->node->id() . '/delete');
    $this->assertResponse(200);
    $this->assertText(t('Report as…'));

    // Login and submit an unprotected node.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/unprotected');
    $edit = array(
      'title[0][value]' => 'unprotected spam',
      'body[0][value]' => 'spam',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertUrl('node/' . $this->node->id());
    $this->assertNoMollomData('node', $this->node->id());

    // Verify that no feedback options appear on the delete confirmation form
    // for the node author.
    $this->drupalGet('node/' . $this->node->id() . '/delete');
    $this->assertResponse(200);
    $this->assertNoText(t('Report as…'));

    // Verify that no feedback options appear for the admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $this->node->id() . '/delete');
    $this->assertResponse(200);
    $this->assertNoText(t('Report as…'));
  }

  /**
   * Tests mass-reporting nodes.
   */
  function testMassReportNodes() {
    $nodes = [];
    $node_storage = \Drupal::entityManager()->getStorage('node');
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('node_article_form');
    $this->drupalLogout();

    // Post 3 nodes.
    $this->drupalLogin($this->webUser);
    foreach (range(0, 2) as $num) {
      $this->drupalGet('node/add/article');
      $edit = array(
        'title[0][value]' => $this->randomMachineName(),
        'body[0][value]' => 'ham',
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
      $this->assertUrl('node/' . $node->id());
      $this->assertMollomData('node', $node->id());
      $nodes[] = $node;
    }

    // Log in as administrator and verify that we can mass-report all
    // nodes to Mollom.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');
    $edit = array(
      'action' => 'node_delete_action',
    );
    /* @var $node \Drupal\node\Entity\Node */
    $i = 0;
    foreach ($nodes as $node) {
      $this->assertText($node->label(), t('Node found.'));
      $edit["node_bulk_form[{$i}]"] = TRUE;
      $i++;
    }
    list(,$minor_version) = explode('.', \Drupal::VERSION);
    $button_name =  $minor_version < 2 ? t('Apply') : t('Apply to selected items');
    $this->drupalPostForm(NULL, $edit, $button_name);
    /* @var $node \Drupal\node\Entity\Node */
    foreach ($nodes as $node) {
      $this->assertText($node->label(), t('Node found.'));
    }
    $edit = array(
      'mollom[feedback]' => 'spam',
    );
    $this->drupalPostForm(NULL, $edit, t('Delete'));
    $this->assertText(t('Deleted @count posts.', array('@count' => count($nodes))));
    $this->assertText(t('The posts were successfully reported as inappropriate.'));

    // Verify that the comments and Mollom session data has been deleted.
    $node_storage->resetCache();
    foreach ($nodes as $node) {
      $this->assertTrue(empty($node_storage->load($node->id())), t('Node was deleted.'));
      $this->assertNoMollomData('node', $node->id());
    }
  }
}

