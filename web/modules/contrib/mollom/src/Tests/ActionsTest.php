<?php

namespace Drupal\mollom\Tests;
use Drupal\mollom\Storage\ResponseDataStorage;
use Drupal\system\Entity\Action;

/**
 * Tests actions provided for comments and nodes.
 * @group mollom
 */
class ActionsTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'views'];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;
  
  protected $useLocal = TRUE;

  function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->addCommentsToNode();
    $this->webUser = $this->drupalCreateUser(['create article content', 'access comments', 'post comments', 'skip comment approval']);

    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('node_article_form');
    $this->setProtectionUI('comment_comment_form');
    $this->drupalLogout();

    // Login and submit a few nodes.
    $this->nodes = array();
    $this->comments = array();
    $this->drupalLogin($this->webUser);
    for ($i = 0; $i < 2; $i++) {
      // Create a test node.
      $edit = [
        'title[0][value]' => 'ham node ' . $i,
      ];
      $this->drupalPostForm('node/add/article', $edit, t('Save'));
      $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

      $this->drupalGet('node/' . $node->id());
      $edit = [
        'comment_body[0][value]' => 'ham',
      ];
      $this->drupalPostForm(NULL, $edit, t('Save'));
    }
  }

  /**
   * Test that calling the mollom action function triggers the unpublish of
   * comments and marking as spam.
   */
  function testCommentActions() {
    $comment_storage = \Drupal::entityManager()->getStorage('comment');
    // Load the comment entity objects.
    $comments = $comment_storage->loadMultiple();
    $contentIds = [];
    /* @var $comment \Drupal\comment\CommentInterface */
    foreach($comments as $comment) {
      $data = ResponseDataStorage::loadByEntity('comment', $comment->id());
      $contentIds[] = $data->contentId;
      $this->assertTrue($comment->isPublished(), 'Initial comment is published');
    }
    $action = Action::load('mollom_comment_unpublish_action');
    $action->execute($comments);

    $this->resetAll();
    foreach ($comments as $comment) {
      $this->assertFalse($comment->isPublished(), 'Comment is now unpublished');
      $server = $this->getServerRecord('feedback');
      $this->assertTrue(in_array($server['contentId'], $contentIds));
      $this->assertEqual($server['source'], 'mollom_action_unpublish_comment');
      $this->assertEqual($server['reason'], 'spam');
      $this->assertEqual($server['type'], 'moderate');
    }
  }

  /**
   * Test that calling the mollom action function triggers the unpublish of
   * nodes and marking as spam.
   */
  function testNodeActions() {
    $node_storage = \Drupal::entityManager()->getStorage('node');
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');
    $this->assertOption('edit-action', 'mollom_node_unpublish_action');

    $contentIds = [];
    $nodes = $node_storage->loadMultiple();
    $i = 0;
    $edit = ['action' => 'mollom_node_unpublish_action'];
    /* @var $node \Drupal\node\NodeInterface */
    foreach($nodes as $node) {
      $edit['node_bulk_form[' . $i . ']'] = TRUE;
      $this->assertTrue($node->isPublished(), 'Initial node is published.');
      $data = ResponseDataStorage::loadByEntity('node', $node->id());
      $contentIds[] = $data->contentId;
      $i++;
    }

    list(,$minor_version) = explode('.', \Drupal::VERSION);
    $button_name =  $minor_version < 2 ? t('Apply') : t('Apply to selected items');
    $this->drupalPostForm(NULL, $edit, $button_name);

    // Verify that all nodes are now unpublished.
    $node_storage->resetCache();
    $nodes = $node_storage->loadMultiple();
    foreach($nodes as $node) {
      $this->assertFalse($node->isPublished(), 'Node is now unpublished.');
      $server = $this->getServerRecord('feedback');
      $this->assertTrue(in_array($server['contentId'], $contentIds));
      $this->assertEqual($server['source'], 'mollom_action_unpublish_node');
      $this->assertEqual($server['reason'], 'spam');
      $this->assertEqual($server['type'], 'moderate');
    }
  }
}
