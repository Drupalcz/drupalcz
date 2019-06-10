<?php

namespace Drupal\dcz_apd\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the APD membership entity.
 *
 * @ingroup dcz_apd
 *
 * @ContentEntityType(
 *   id = "apd_membership",
 *   label = @Translation("APD membership"),
 *   handlers = {
 *     "storage" = "Drupal\dcz_apd\ApdMembershipStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dcz_apd\ApdMembershipListBuilder",
 *     "views_data" = "Drupal\dcz_apd\Entity\ApdMembershipViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dcz_apd\Form\ApdMembershipForm",
 *       "add" = "Drupal\dcz_apd\Form\ApdMembershipForm",
 *       "edit" = "Drupal\dcz_apd\Form\ApdMembershipForm",
 *       "delete" = "Drupal\dcz_apd\Form\ApdMembershipDeleteForm",
 *     },
 *     "access" = "Drupal\dcz_apd\ApdMembershipAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dcz_apd\ApdMembershipHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "apd_membership",
 *   revision_table = "apd_membership_revision",
 *   revision_data_table = "apd_membership_field_revision",
 *   admin_permission = "administer apd membership entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "profile_id" = "profile_id",
 *     "valid_from" = "valid_from",
 *     "valid_to" = "valid_to",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/apd_membership/{apd_membership}",
 *     "add-form" = "/admin/structure/apd_membership/add",
 *     "edit-form" = "/admin/structure/apd_membership/{apd_membership}/edit",
 *     "delete-form" =
 *   "/admin/structure/apd_membership/{apd_membership}/delete",
 *     "version-history" =
 *   "/admin/structure/apd_membership/{apd_membership}/revisions",
 *     "revision" =
 *   "/admin/structure/apd_membership/{apd_membership}/revisions/{apd_membership_revision}/view",
 *     "revision_revert" =
 *   "/admin/structure/apd_membership/{apd_membership}/revisions/{apd_membership_revision}/revert",
 *     "revision_delete" =
 *   "/admin/structure/apd_membership/{apd_membership}/revisions/{apd_membership_revision}/delete",
 *     "collection" = "/admin/structure/apd_membership",
 *   },
 *   field_ui_base_route = "apd_membership.settings"
 * )
 */
class ApdMembership extends RevisionableContentEntityBase implements ApdMembershipInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller,
                                   array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the apd_membership owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setValid($status) {
    $this->set('status', $status ? TRUE : FALSE);
    return $this;
  }

  /**
   * Gets the APD membership profile.
   *
   * @return string
   *   ID of the APD membership profile.
   */
  public function getProfileId() {
    return $this->get('profile_id')->target_id;
  }

  /**
   * Sets the APD membership profile ID.
   *
   * @param int $pid
   *   The APD membership profile ID.
   *
   * @return \Drupal\dcz_apd\Entity\ApdMembershipInterface
   *   The called APD membership entity.
   */
  public function setProfileId($pid) {
    $this->set('profile_id', $pid);
    return $this;
  }

  /**
   * Returns the APD membership valid status indicator.
   *
   * Invalidated APD membership are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the APD membership is valid.
   */
  public function isValid() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user ID of author created the APD membership entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['profile_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Profile ID'))
      ->setDescription(t('The profile ID of an APD member.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'profile',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('APD membership is valid'))
      ->setDescription(t('A boolean indicating whether the APD membership is valid.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ]);

    $fields['valid_from'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Membership valid since'))
      ->setDescription(t('The time since the entity is valid.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime',
        'weight' => 3,
      ]);

    $fields['valid_to'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Membership valid to'))
      ->setDescription(t('The time the entity is valid to.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime',
        'weight' => 4,
      ]);;

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
