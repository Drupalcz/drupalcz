<?php

namespace Drupal\mollom\Tests;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\AccountInterface;
use Drupal\mollom\Controller\FormController;
use Drupal\mollom\Entity\FormInterface;
use Drupal\mollom\Storage\ResponseDataStorage;

/**
 * Verify that form registry information is properly transformed into data that
 * is sent to Mollom servers.
 *
 * @group mollom
 */
class DataTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  protected $useLocal = TRUE;

  /**
   * Test mollom_form_get_values().
   */
  function testFormGetValues() {
    // Load the context node.
    $node = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);

    // Form registry information.
    $form_info = [
      'elements' => [
        'subject' => 'Subject',
        'message' => 'Message',
        'parent][child' => 'Some nested element',
        'field_checked' => 'Field to check',
        'field_unchecked' => 'Field to ignore',
      ],
      'mapping' => [
        'post_title' => 'subject',
        'author_name' => 'name',
        'author_mail' => 'mail',
        'context_id' => 'nid',
      ],
    ];
    // Fields configured via Mollom admin UI based on $form_info['elements'].
    $fields = [
      'subject',
      'message',
      'parent][child',
      'field_checked',
    ];

    // Verify submitted form values for an anonymous/arbitrary user.
    \Drupal::getContainer()->set('current_user', new AnonymousUserSession());
    $values = [
      'subject' => 'Foo',
      'message' => 'Bar',
      'parent' => [
        'child' => 'Beer',
      ],
      'field_checked' => [
        'und' => [
          0 => ['value' => ''],
          1 => ['summary' => 'Check summary', 'value' => 'Check first', 'whatever' => 'whatever'],
          2 => ['value' => 'Check second'],
          3 => ['nested_empty' => []],
          4 => ['nested_weird' => ['']],
        ],
      ],
      'field_unchecked' => [
        'und' => [
          0 => ['value' => 'Ignore first'],
          1 => ['value' => 'Ignore second'],
        ],
      ],
      'name' => 'Drupaler',
      'mail' => 'drupaler@example.com',
      'nid' => $node->id(),
    ];
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setButtons([]);
    $form_state->setValue(['mollom', 'context created callback'], 'node_mollom_context_created');
    $data = FormController::extractMollomValues($form_state, $fields, $form_info['mapping']);
    $data += array_fill_keys(['postTitle', 'postBody', 'authorId', 'authorCreated', 'authorName', 'authorMail', 'authorUrl', 'authorOpenid', 'authorIp', 'contextCreated'], NULL);

    $this->assertSame('postTitle', $data['postTitle'], $values['subject']);
    $body = [
      $values['message'],
      $values['parent']['child'],
      $values['field_checked']['und'][1]['summary'],
      $values['field_checked']['und'][1]['value'],
      $values['field_checked']['und'][2]['value'],
    ];
    $this->assertSame('postBody', $data['postBody'], implode("\n", $body));
    $this->assertSame('authorId', $data['authorId'], NULL);
    $this->assertSame('authorCreated', $data['authorCreated'], NULL);
    $this->assertSame('authorName', $data['authorName'], $values['name']);
    $this->assertSame('authorMail', $data['authorMail'], $values['mail']);
    $this->assertSame('authorUrl', $data['authorUrl'], NULL);
    $this->assertSame('authorIp', $data['authorIp'], \Drupal::request()->getClientIp());
    $this->assertSame('contextCreated', $data['contextCreated'], $node->getCreatedTime());

    // Verify submitted form values for an anonymous user whose name happens to
    // match a registered user.
    $values = [
      'subject' => 'Foo',
      'message' => 'Bar',
      'parent' => [
        'child' => 'Beer',
      ],
      'field_checked' => [
        0 => ['value' => 'Check first'],
        1 => ['value' => 'Check second'],
      ],
      'field_unchecked' => [
        0 => ['value' => 'Ignore first'],
        1 => ['value' => 'Ignore second'],
      ],
      'name' => $this->adminUser->getAccountName(),
      'nid' => $node->id(),
    ];
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setButtons([]);
    $form_state->setValue(['mollom', 'context created callback'], 'node_mollom_context_created');
    $data = FormController::extractMollomValues($form_state, $fields, $form_info['mapping']);
    $data += array_fill_keys(['postTitle', 'postBody', 'authorId', 'authorCreated', 'authorName', 'authorMail', 'authorUrl', 'authorOpenid', 'authorIp', 'contextCreated'], NULL);

    $this->assertSame('postTitle', $data['postTitle'], $values['subject']);
    $body = [
      $values['message'],
      $values['parent']['child'],
      $values['field_checked'][0]['value'],
      $values['field_checked'][1]['value'],
    ];
    $this->assertSame('postBody', $data['postBody'], implode("\n", $body));
    $this->assertSame('authorId', $data['authorId'], NULL);
    $this->assertSame('authorCreated', $data['authorCreated'], NULL);
    $this->assertSame('authorName', $data['authorName'], $values['name']);
    $this->assertSame('authorMail', $data['authorMail'], NULL);
    $this->assertSame('authorUrl', $data['authorUrl'], NULL);
    $this->assertSame('authorIp', $data['authorIp'], \Drupal::request()->getClientIp());
    $this->assertSame('contextCreated', $data['contextCreated'], $node->getCreatedTime());

    // Verify submitted form values for a registered user.
    $admin_session = new UserSession([
      'uid' => $this->adminUser->id(),
      'name' => $this->adminUser->getAccountName(),
      'mail' => $this->adminUser->getEmail(),
    ]);
    \Drupal::getContainer()->set('current_user', $admin_session);
    $values = [
      'subject' => 'Foo',
      'message' => 'Bar',
      'name' => $this->adminUser->getAccountName(),
      'nid' => $node->id(),
    ];
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setButtons([]);
    $form_state->setValue(['mollom', 'context created callback'], 'node_mollom_context_created');
    $data = FormController::extractMollomValues($form_state, $fields, $form_info['mapping']);
    $data += array_fill_keys(['postTitle', 'postBody', 'authorId', 'authorCreated', 'authorName', 'authorMail', 'authorUrl', 'authorOpenid', 'authorIp', 'contextCreated'], NULL);

    $this->assertSame('postTitle', $data['postTitle'], $values['subject']);
    $this->assertSame('postBody', $data['postBody'], $values['message']);
    $this->assertSame('authorId', $data['authorId'], $this->adminUser->id());
    $this->assertSame('authorCreated', $data['authorCreated'], $this->adminUser->getCreatedTime());
    $this->assertSame('authorName', $data['authorName'], $this->adminUser->getAccountName());
    $this->assertSame('authorMail', $data['authorMail'], $this->adminUser->getEmail());
    $this->assertSame('authorUrl', $data['authorUrl'], NULL);
    $this->assertSame('authorIp', $data['authorIp'], \Drupal::request()->getClientIp());
    $this->assertSame('contextCreated', $data['contextCreated'], $node->getCreatedTime());

    // Verify that invalid UTF-8 is detected.
    $values = [
      'subject' => "Foo \xC0 bar",
    ];
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setButtons([]);
    $data = FormController::extractMollomValues($form_state, $fields, $form_info['mapping']);
    $this->assertFalse($data, 'Invalid UTF-8 detected.');
    $this->assertMollomWatchdogMessages();
    // Verify that invalid UTF-8 is detected in the CAPTCHA solution element.
    $values = [
      'mollom' => [
        'captcha' => [
          'captcha_input' => "Foo \xC0 bar",
        ],
      ],
    ];
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setButtons([]);
    $data = FormController::extractMollomValues($form_state, $fields, $form_info['mapping']);
    $this->assertFalse($data, 'Invalid UTF-8 detected in CAPTCHA solution.');
    $this->assertMollomWatchdogMessages();

    // Verify that invalid XML characters are detected.
    $values = [
      'subject' => "Foo \x11 bar",
    ];
    $form_state = new FormState();
    $form_state->setValues($values);
    $form_state->setButtons([]);
    $data = FormController::extractMollomValues($form_state, $fields, $form_info['mapping']);
    $this->assertFalse($data, 'Invalid XML characters detected.');
    $this->assertMollomWatchdogMessages();
  }

  /**
   * Test that form button values are not contained in postBody sent to Mollom.
   */
  function testFormButtonValues() {
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('mollom_test_post_form');
    $this->drupalLogout();

    // Verify that neither the "Submit" nor the "Add" button value is contained
    // in the post body.
    $edit = [
      'title' => 'ham',
      'body' => 'ham',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $data = $this->getServerRecord();
    $this->assertFalse(preg_match('@Submit|Add@', $data['postBody']), 'Button values not found in post body.');
  }

  /**
   * Test submitted post and author information for textual analysis.
   */
  function testAnalysis() {
    // Create Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Make comment preview optional and grant permissions.
    $this->addCommentsToNode('article');

    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access comments', 'post comments']);
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['access comments', 'post comments', 'skip comment approval']);
    // Add 'administer comments' permission to $adminUser.
    $this->addPermissionsToAdmin(['administer comments']);

    // Rebuild menu router, permissions, etc.
    // @todo Remove in D8.
    $this->resetAll();

    $this->drupalLogin($this->adminUser);
    $this->setProtection('comment_comment_form');

    // Create a node we can comment on.
    $node = $this->drupalCreateNode(['type' => 'article', 'promote' => 1]);
    $node_url = 'node/' . $node->id();
    $this->drupalGet($node_url);
    $this->assertText($node->getTitle());
    $this->drupalLogout();

    // Log in regular user and post a comment.
    $web_user = $this->drupalCreateUser();
    $this->drupalLogin($web_user);
    $this->drupalGet($node_url);

    $edit = array(
      'subject[0][value]' => $this->randomString(),
      'comment_body[0][value]' => 'unsure',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(self::UNSURE_MESSAGE);

    // Verify that submitted data equals post data.
    $data = $this->getServerRecord();
    $this->assertSame('postTitle', $data['postTitle'], $edit['subject[0][value]']);
    $this->assertSame('postBody', $data['postBody'], $edit['comment_body[0][value]']);
    $this->assertSame('authorName', $data['authorName'], $web_user->getAccountName());
    $this->assertSame('authorMail', $data['authorMail'], $web_user->getEmail());
    $this->assertSame('authorId', $data['authorId'], $web_user->id());
    $this->assertSame('strictness', $data['strictness'], 'normal');

    $this->PostCorrectCaptcha(NULL, array(), t('Save'));
    $cids = $this->loadCommentsBySubject($edit['subject[0][value]']);
    $this->assertTrue(!empty($cids), t('Comment exists in database.'));

    // Verify that submitted data equals post data.
    $data = $this->getServerRecord('captcha');
    $this->assertSame('authorId', $data['authorId'], $web_user->id());

    // Allow anonymous users to post comments without approval.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['skip comment approval']);

    // Allow anonymous users to post contact information.
    $this->setCommentSettings('anonymous', COMMENT_ANONYMOUS_MAY_CONTACT);

    // Log out and post a comment as anonymous user.
    $this->resetServerRecords();
    $this->drupalLogout();
    $this->drupalGet($node_url);
    $this->clickLink(t('Add new comment'));
    // Ensure we have some potentially escaped characters in the values.
    $edit = array(
      'name' => $this->randomString(6) . ' & ' . $this->randomString(8),
      'subject[0][value]' => '"' . $this->randomString() . '"',
      'comment_body[0][value]' => 'unsure',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(self::UNSURE_MESSAGE);

    // Verify that submitted data equals post data.
    $data = $this->getServerRecord();
    $this->assertSame('postTitle', $data['postTitle'], $edit['subject[0][value]']);
    $this->assertSame('postBody', $data['postBody'], $edit['comment_body[0][value]']);
    $this->assertSame('authorName', $data['authorName'], $edit['name']);
    $this->assertFalse(isset($data['authorId']), t('authorId: Undefined.'));

    $this->PostCorrectCaptcha(NULL, array(), t('Save'));
    $cids = $this->loadCommentsBySubject($edit['subject[0][value]']);
    $this->assertTrue(!empty($cids), t('Comment exists in database.'));

    // Verify that submitted data equals post data.
    $data = $this->getServerRecord('captcha');
    $this->assertFalse(isset($data['authorId']), t('authorId: Undefined.'));

    // Log in admin user and edit comment containing spam.
    $this->resetServerRecords();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('comment/' . reset($cids) . '/edit');
    // Post without modification.
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Verify that no data was submitted to Mollom.
    $data = $this->getServerRecord();
    $this->assertFalse($data, t('Administrative form submission was not validated by Mollom.'));
  }

  /**
   * Tests that protected forms contain a hidden honeypot field and its value is recorded.
   */
  function testHoneypot() {
    // Enable protection for mollom_test_post_form.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('mollom_test_post_form');
    $this->drupalLogout();

    // Verify that the hidden honeypot field is output.
    $this->drupalGet('mollom-test/form');
    $this->assertFieldByName('mollom[homepage]', '', t('Hidden honeypot field found.'));

    // Verify that a honeypot value is sent to mollom.checkContent.
    $edit = [
      'title' => 'unsure',
      'body' => 'unsure',
      'mollom[homepage]' => 'HONEYPOT-VALUE',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertCaptchaField();
    $data = $this->getServerRecord();
    $this->assertSame('honeypot', $data['honeypot'], $edit['mollom[homepage]']);

    $this->postCorrectCaptcha(NULL, [], t('Save'), 'Successful form submission.');
    $data = $this->getServerRecord();
    $this->assertSame('honeypot', $data['honeypot'], $edit['mollom[homepage]']);
    $data = $this->getServerRecord('captcha');
    $this->assertSame('honeypot', $data['honeypot'], $edit['mollom[homepage]']);

    // Change form protection to CAPTCHA only.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('mollom_test_post_form', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->drupalLogout();
    $this->resetServerRecords();
    $this->resetAll();

    // Verify that the hidden honeypot field is output.
    $this->drupalGet('mollom-test/form');
    $this->assertFieldByName('mollom[homepage]', '', t('Hidden honeypot field found.'));

    // Verify that a honeypot value is sent to mollom.checkContent.
    // postCorrectCaptcha() cannot be used for mollom_test_post_form, since the form
    // is re-displayed again after a successful form submission.
    $edit = [
      'title' => $this->randomString(),
      self::CAPTCHA_INPUT => 'correct',
      'mollom[homepage]' => 'HONEYPOT-VALUE',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Successful form submission.');
    $data = $this->getServerRecord('captcha');
    $this->assertSame('honeypot', $data['honeypot'], $edit['mollom[homepage]']);
  }

  /**
   * Tests automated 'post_id' mapping and session data storage.
   *
   * This is an atomic test to verify that a simple 'post_id' mapping defined
   * via hook_mollom_form_info() is sufficient for basic integration with
   * Mollom (without reporting).
   */
  function testPostIdMapping() {
    // Enable protection for mollom_test_post_form.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('mollom_test_post_form');
    $this->drupalLogout();

    // Submit a mollom_test thingy.
    $edit = [
      'title' => 'ham',
      'body' => $this->randomString(),
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertText('Successful form submission.');
    $mid = $this->getFieldValueByName('mid');
    $this->assertTrue($mid > 0, t('Submission was stored.'));
    $data = $this->assertMollomData('mollom_test_post', $mid);

    // Ensure we were redirected to the form for the stored entry.
    $this->assertFieldByName('body', $edit['body'], t('Existing body value found.'));
    $new_mid = $this->getFieldValueByName('mid');
    $this->assertEqual($new_mid, $mid, t('Existing entity id found.'));

    // Verify that session data was stored.
    $this->assertSame('entity', $data->entity, 'mollom_test_post');
    $this->assertSame('id', $data->id, $mid);
    $this->assertSame('form_id', $data->form_id, 'mollom_test_post_form');
    $stored = ResponseDataStorage::loadByEntity('mollom_test_post', $mid);
    $this->assertEqual(count($stored), 1, t('Data was stored in {mollom}.'));

    // Update the stored entry.
    $edit['title'] = 'unsure';
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertCaptchaField();
    $contentId = $this->assertResponseIDInForm('contentId');
    $captchaId = $this->assertResponseIDInForm('captchaId');
    $this->postCorrectCaptcha(NULL, [], t('Save'), 'Successful form submission.');
    $new_data = $this->assertMollomData('mollom_test_post', $mid);

    // Verify that only session data was updated.
    $this->assertSame('entity', $data->entity, $new_data->entity);
    $this->assertSame('id', $data->id, $new_data->id);
    $this->assertNotSame('contentId', $data->contentId, $new_data->contentId);
    $this->assertNotSame('captchaId', $data->captchaId, $new_data->captchaId);
    $this->assertSame('form_id', $data->form_id, $new_data->form_id);
    $this->assertSame('qualityScore', $data->qualityScore, $new_data->qualityScore);
    $stored = ResponseDataStorage::loadByEntity($data->entity, $data->id);
    $this->assertEqual(count($stored), 1, t('Stored data in {mollom} was updated.'));
  }

  /**
   * Tests data sent for Mollom::verifyKeys().
   */
  function testVerifyKeys() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/mollom/settings');

    // Verify that we additionally sent version data.
    $data = $this->getServerRecord('sites');
    $info = $this->getClient()->getClientInformation();
    $this->assertTrue(!empty($info['platformName']), t('Version information found.'));
    $this->assertSame('platformName', $data['platformName'], $info['platformName']);
    $this->assertSame('platformVersion', $data['platformVersion'], $info['platformVersion']);
    $this->assertSame('clientName', $data['clientName'], $info['clientName']);
    $this->assertSame('clientVersion', $data['clientVersion'], $info['clientVersion']);
  }
}
