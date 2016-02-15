<?php

namespace Drupal\mollom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for index entities.
 */
interface FormInterface extends ConfigEntityInterface {



  /**
   * Form protection mode: No protection.
   */
  const MOLLOM_MODE_DISABLED = 0;

  /**
   * Form protection mode: CAPTCHA-only protection.
   */
  const MOLLOM_MODE_CAPTCHA = 1;

  /**
   * Form protection mode: Text analysis with fallback to CAPTCHA.
   */
  const MOLLOM_MODE_ANALYSIS = 2;

  /**
   * Server communication failure fallback mode: Block all submissions of protected forms.
   */
  const MOLLOM_FALLBACK_BLOCK = 0;

  /**
   * Server communication failure fallback mode: Accept all submissions of protected forms.
   */
  const MOLLOM_FALLBACK_ACCEPT = 1;

  /**
   * Set defaults based on a form definition if the entity is new, otherwise
   * gets the form information for the existing form configuration.
   *
   * This somewhat corresponds to mollom_form_new in previous versions.
   *
   * @param string $form_id
   *   The id of the form that will be protected.
   * @return array
   *   An array of default protected form information.
   */
  public function initialize($form_id = NULL);

  /**
   * Get the types of checks to perform.
   * @return array
   */
  public function getChecks();

  /**
   * Sets the types of checks to perform.
   *
   * @param array $checks
   *   An array checks to perform.  May include: spam,
   *   profanity, language.
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setChecks($checks);

  /**
   * Gets the type of protection for the form - textual or captcha.
   * @return number
   *   One of self::MOLLOM_MODE_ANALYSIS or self::MOLLOM_MODE_CAPTCHA
   */
  public function getProtectionMode();

  /**
   * Sets the type of protection for the form.
   *
   * @param $mode
   *   One of self::MOLLOM_MODE_ANALYSIS or self::MOLLOM_MODE_CAPTCHA
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setProtectionMode($mode);

  /**
   * An array of fields to be checked within the form.
   * @return array
   */
  public function getEnabledFields();

  /**
   * Sets the array of fields that can be checked within the form as body.
   * @param array $fields
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setEnabledFields(array $fields);

  /**
   * The level of strictness Mollom should use when checking content submitted
   * through this form.
   *
   * @return string
   */
  public function getStrictness();

  /**
   * Sets the level of strictness Mollom should use to check this form.
   *
   * @param $level
   *   One of: strict, normal, relaxed.
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setStrictness($level);

  /**
   * What to do when Mollom returns an unsure for content submitted through this
   * form.
   * @return string
   */
  public function getUnsure();

  /**
   * Sets how the module will handle content submitted through this form that
   * Mollom is unsure about.
   *
   * @param string $handling
   *   One of moderate, captcha, binary.
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setUnsure($handling);

  /**
   * Indicates if the module should discard spam from this form or keep for
   * moderation.
   * @return boolean
   */
  public function getDiscard();

  /**
   * Sets whether the module should discard spam (TRUE) or keep for moderation
   * (FALSE).
   * @param boolean $discard
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setDiscard($discard);

  /**
   * Gets the mapping of field values for Mollom submissions.
   * @return array
   */
  public function getMapping();

  /**
   * Sets the mapping of field values for Mollom submissions.
   * @param array $mapping
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setMapping(array $mapping);

  /**
   * Gets the name of the module that owns the form being protected.
   * @return string
   */
  public function getModule();

  /**
   * Sets the name of the module that owns the forms being protected.
   * @param $module
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setModule($module);

  /**
   * Get the entity id of the entity form being protected.
   * @return string
   */
  public function getEntity();

  /**
   * Set the entity id of the entity form being protected.
   * @param string $entity
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setEntity($entity);

  /**
   * Get the bundle id of the entity bundle being protected.
   * @return string
   */
  public function getBundle();

  /**
   * Sets the entity bundle id of the entity bundle being protected.
   * @param string $bundle
   * @return \Drupal\mollom\Entity\FormInterface
   */
  public function setBundle($bundle);
}
