<?php

namespace Drupal\mollom\Tests;
use Drupal\mollom\Controller\FormController;
use Drupal\mollom\Entity\Form;
use Drupal\mollom\Entity\FormInterface;

/**
 * Verify that forms can be properly protected and unprotected.
 * @group mollom
 */
class FormConfigurationTest extends MollomTestBase {

  public static $modules = ['dblog', 'mollom', 'node', 'comment', 'mollom_test_server', 'mollom_test'];

  protected $useLocal = TRUE;

  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests configuration of form fields for textual analysis.
   */
  function testFormFieldsConfiguration() {
    $form_info = FormController::getProtectedFormDetails('mollom_test_post_form', 'mollom_test');

    // Protect Mollom test form.
    $this->drupalGet('admin/config/content/mollom/add-form', ['query' => ['form_id' => 'mollom_test_post_form']]);
    $this->assertText('Mollom test form');

    $edit = [
      'mode' => FormInterface::MOLLOM_MODE_ANALYSIS,
      'checks[spam]' => TRUE,
      'enabled_fields[title]' => TRUE,
      'enabled_fields[body]' => TRUE,
      'enabled_fields[exclude]' => FALSE,
      'enabled_fields[' . rawurlencode('parent][child') . ']' => TRUE,
      'enabled_fields[field]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Create Protected Mollom Form'));

    // Verify that mollom_test_post_form form was protected.
    $this->assertText(t('The form protection has been added.'));
    $this->assertText('Mollom test form');
    $mollom_form = $this->loadMollomConfiguredForm('mollom_test_post_form');
    $this->assertTrue($mollom_form, t('Form configuration exists.'));

    // Verify that field configuration was properly stored.
    $this->drupalGet('admin/config/content/mollom/form/mollom_test_post_form/edit');
    foreach ($edit as $name => $value) {
      // Skip any inputs that are not the fields for analysis checkboxes.
      if (strpos($name, '[enabled_fields]') === FALSE) {
        continue;
      }
      // assertFieldByName() does not work for checkboxes.
      // @see assertFieldChecked()
      $elements = $this->xpath('//input[@name=:name]', array(':name' => $name));
      if (isset($elements[0])) {
        if ($value) {
          $this->assertTrue(!empty($elements[0]['checked']), t('Field @name is checked', array('@name' => $name)));
        }
        else {
          $this->assertTrue(empty($elements[0]['checked']), t('Field @name is not checked', array('@name' => $name)));
        }
      }
      else {
        $this->fail(t('Field @name not found.', array('@name' => $name)));
      }
    }

    // Remove the title field from those that were enabled.
    $test_enabled_fields = ['body', 'exclude', 'parent][child', 'field'];
    $mollom_form->setEnabledFields($test_enabled_fields)->save();

    // Try a submit of the form.
    $this->drupalLogout();
    $edit = [
      'title' => 'unsure',
      'body' => 'unsure',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertText(self::UNSURE_MESSAGE);

    $data = $this->getServerRecord();
    $this->assertTrue(empty($data['postTitle']), 'Post title was not passed to Mollom.');

    // Add the title back.
    $this->drupalLogin($this->adminUser);
    $test_enabled_fields[] = 'title';
    // Add a field to the stored configuration that existed previously.
    $test_enabled_fields[] = 'orphan_field';
    $mollom_form->setEnabledFields($test_enabled_fields)->save();

    // Verify that field configuration contains only available elements.
    $this->drupalGet('admin/config/content/mollom/form/mollom_test_post_form/edit');
    $fields = $this->xpath('//input[starts-with(@name, "enabled_fields")]');
    $elements = array();
    foreach ($fields as $field) {
      // Strip out 'enabled_fields[' from the start and ']' from the end. 
      $elements[] = substr(substr(rawurldecode($field['name']), 0, -1), 15);
    }
    $this->assertEqual($elements, array_keys($form_info['elements']), t('Field list only contains available form elements.'));

    // Try a simple submit of the form.
    $this->drupalLogout();
    $edit = [
      'title' => 'unsure',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertNoText('Successful form submission.');
    $this->assertText(self::UNSURE_MESSAGE);
    $this->postCorrectCaptcha(NULL, array(), t('Save'), t('Successful form submission.'));

    // Try to submit values for top-level fields.
    $edit = [
      'title' => 'spam',
      'body' => 'spam',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertNoText('Successful form submission.');
    $this->assertNoText(self::UNSURE_MESSAGE);
    $this->assertText(self::SPAM_MESSAGE);

    // Try to submit values for nested field.
    $edit = [
      'title' => $this->randomString(),
      'parent[child]' => 'spam',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertNoText('Successful form submission.');
    $this->assertNoText(self::UNSURE_MESSAGE);
    $this->assertText(self::SPAM_MESSAGE);

    // Try to submit values for nested field and multiple value field.
    // Start with ham values for simple, nested, and first multiple field.
    $edit = [
      'title' => 'ham',
      'parent[child]' => 'ham',
      'field[new]' => 'ham',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Add'));

    // Verify that the form was rebuilt.
    $this->assertNoText('Successful form submission.');
    $this->assertNoText(self::UNSURE_MESSAGE);
    $this->assertNoText(self::SPAM_MESSAGE);

    // Add another value for multiple field.
    $edit = [
      'field[new]' => 'ham',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add'));

    // Verify that the form was rebuilt.
    $this->assertNoText('Successful form submission.');
    $this->assertNoText(self::UNSURE_MESSAGE);
    $this->assertNoText(self::SPAM_MESSAGE);

    // Now replace all ham values with random values, add a spam value to the
    // multiple field and submit the form.
    $edit = [
      'title' => $this->randomString(),
      'parent[child]' => $this->randomString(),
      'field[0]' => $this->randomString(),
      'field[1]' => $this->randomString(),
      'field[new]' => 'spam',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Verify that the form was not submitted and cannot be submitted.
    $this->assertNoText('Successful form submission.');
    $this->assertText(self::SPAM_MESSAGE);

    // Verify that we can remove the form protection.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/mollom');
    $this->assertText('Mollom test form');

    $this->drupalPostForm('admin/config/content/mollom/form/mollom_test_post_form/delete', array(), t('Remove Mollom Protection'));
    $this->assertUrl('admin/config/content/mollom');
    $this->assertNoText('Mollom test form');
    $this->assertText(t('The form protection has been removed.'));
    $mollom_form = $this->loadMollomConfiguredForm('mollom_test_post_form');
    $this->assertFalse($mollom_form, t('Form protection not found.'));

    // Verify that the form is no longer protected.
    $this->drupalLogout();
    $edit = [
      'title' => 'unsure',
    ];
    $this->drupalPostForm('mollom-test/form', $edit, t('Save'));
    $this->assertText('Successful form submission.');
    $this->assertNoText(self::UNSURE_MESSAGE);
    $this->assertNoCaptchaField();
  }

  // @todo: This should be moved to a group of unit tests.
  function testFormProtectableFields() {
    $form_info = [];
    FormController::addProtectableFields($form_info, 'mollom_test_post', 'mollom_test_post');
    $expected = [
      'title',
      'body',
    ];
    $unexpected = [
      'mid',
      'uuid',
      'status',
      'readonly',
      'computed',
    ];
    foreach ($expected as $field) {
      $this->assertTrue(isset($form_info['elements'][$field]));
    }
    foreach ($unexpected as $field) {
      $this->assertFalse(isset($form_info['elements'][$field]));
    }
    $this->assertEqual($form_info['mapping']['post_id'], 'mid');
  }

  /**
   * Tests default configuration, protecting, and unprotecting forms.
   */
  function testFormAdministration() {
    $form_info = FormController::getProtectableForms();
    foreach ($form_info as $form_id => $info) {
      $form_info[$form_id] += FormController::getProtectedFormDetails($form_id, $info['module']);
    }

    // Verify that user registration form is not protected.
    $this->drupalGet('admin/config/content/mollom');
    $this->assertNoText($form_info['user_register_form']['title']);
    $this->assertFalse($this->loadMollomConfiguredForm('user_register_form'), t('Form configuration does not exist.'));

    // Re-protect user registration form.
    $this->drupalGet('admin/config/content/mollom/add-form');
    $this->assertNoText(t('All available forms are protected already.'));
    $this->drupalGet('admin/config/content/mollom/add-form', ['query' => ['form_id' => 'user_register_form']]);
    $this->assertText($form_info['user_register_form']['title']);
    $this->drupalPostForm(NULL, array(), t('Create Protected Mollom Form'));

    // Verify that user registration form was protected.
    $this->assertText(t('The form protection has been added.'));
    $this->assertText($form_info['user_register_form']['title']);
    $this->assertTrue($this->loadMollomConfiguredForm('user_register_form'), t('Form configuration exists.'));

    // Retrieve a list of all permissions to verify them below.
    $all_permissions = \Drupal::service('user.permissions')->getPermissions();

    // Iterate over all unconfigured forms and protect them.
    foreach ($form_info as $form_id => $info) {
      if (!$this->loadMollomConfiguredForm($form_id)) {
        $this->drupalGet('admin/config/content/mollom/add-form', ['query' => ['form_id' => $form_id]]);
        $this->assertText($info['title']);
        // Verify that forms specifying elements have all possible elements
        // preselected for textual analysis.
        $edit = array();
        if (!empty($info['elements'])) {
          $edit['checks[spam]'] = TRUE;

          foreach ($info['elements'] as $field => $label) {
            $field = rawurlencode($field);
            $this->assertFieldByName("enabled_fields[$field]", TRUE);
          }
        }
        // Verify that CAPTCHA-only forms contain no configurable fields.
        else {
          $this->assertNoText(t('Analyze text for'));
          $this->assertNoText(t('Text fields to analyze'));
        }
        // Verify that bypass permissions are output.
        $this->assertRaw($all_permissions['bypass mollom protection']['title']);
        foreach ($info['bypass access'] as $permission) {
          $this->assertRaw($all_permissions[$permission]['title']);
        }
        $this->drupalPostForm(NULL, $edit, t('Create Protected Mollom Form'));
        $this->assertText(t('The form protection has been added.'));
      }
    }

    // Verify that trying to add a form redirects to the overview.
    $this->drupalGet('admin/config/content/mollom/add-form');
    $this->assertText(t('All available forms are protected already.'));
    $this->assertUrl('admin/config/content/mollom');
  }

  /**
   * Tests invalid (stale) form configurations.
   */
  function testInvalidForms() {
    $forms = [
      'nonexisting' => 'nonexisting_form',
      'user' => 'user_nonexisting_form',
      'node' => 'nonexisting_node_form',
      'comment' => 'comment_node_nonexisting_form',
    ];
    $mode = 0;
    foreach ($forms as $module => $form_id) {
      $mollom_form = FormController::getProtectedFormDetails($form_id, $module, []);
      $mollom_form['mode'] = $mode++;
      $form = Form::create($mollom_form);
      $form->id = $form_id;
      $form->save();
    }

    // Just visiting the form administration page is sufficient; it will throw
    // fatal errors, warnings, and notices.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/mollom');

    // Ensure that unprotecting the forms does not throw any notices either.
    foreach ($forms as $form_id) {
      $this->assertNoLinkByHref('admin/config/content/mollom/form/' . $form_id . '/edit');
      $this->assertLinkByHref('admin/config/content/mollom/form/' . $form_id . '/delete');
      $this->drupalPostForm('admin/config/content/mollom/form/' . $form_id . '/delete', array(), t('Remove Mollom Protection'));
      $this->assertNoLinkByHref('admin/config/content/mollom/form/' . $form_id . '/delete');
    }
    // Confirm deletion.
    $configured = \Drupal::entityManager()->getStorage('mollom_form')->loadMultiple();
    $this->assertFalse($configured, 'No forms found.');
  }

  /**
   * Tests programmatically, conditionally disabling Mollom.
   */
  function testFormAlter() {
    // Enable CAPTCHA-only protection for request user password form.
    $this->drupalLogin($this->adminUser);
    $this->setProtectionUI('user_pass', FormInterface::MOLLOM_MODE_CAPTCHA);
    $this->drupalLogout();

    // Conditionally disable protection and verify again.
    \Drupal::state()->set('mollom_test.disable_mollom', TRUE);
    $this->drupalGet('');
    $this->drupalGet('user/password');
    $this->assertNoCaptchaField();
  }

  /**
   * Helper function to load the Mollom configuration for a protected form.
   *
   * @param $id
   *   The form ID to load protection details for.
   * @return \Drupal\mollom\Entity\FormInterface|null
   */
  protected function loadMollomConfiguredForm($id) {
    return \Drupal::entityManager()->getStorage('mollom_form')->load($id);
  }
}

