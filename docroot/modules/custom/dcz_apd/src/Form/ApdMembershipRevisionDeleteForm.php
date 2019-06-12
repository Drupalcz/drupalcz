<?php

namespace Drupal\dcz_apd\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a APD membership revision.
 *
 * @ingroup dcz_apd
 */
class ApdMembershipRevisionDeleteForm extends ConfirmFormBase {

  use MessengerTrait;

  /**
   * The APD membership revision.
   *
   * @var \Drupal\dcz_apd\Entity\ApdMembershipInterface
   */
  protected $revision;

  /**
   * The APD membership storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ApdMembershipStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ApdMembershipRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter service.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection, DateFormatterInterface $dateFormatter) {
    $this->ApdMembershipStorage = $entity_storage;
    $this->connection = $connection;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('apd_membership'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'apd_membership_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.apd_membership.version_history', ['apd_membership' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $apd_membership_revision = NULL) {
    $this->revision = $this->ApdMembershipStorage->loadRevision($apd_membership_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->ApdMembershipStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')
      ->notice('APD membership: deleted %title revision %revision.', [
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]);
    $this->messenger()
      ->addStatus($this->t('Revision from %revision-date of APD membership %title has been deleted.', [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
        '%title' => $this->revision->label(),
      ]));
    $form_state->setRedirect(
      'entity.apd_membership.canonical',
      ['apd_membership' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {apd_membership_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.apd_membership.version_history',
        ['apd_membership' => $this->revision->id()]
      );
    }
  }

}
