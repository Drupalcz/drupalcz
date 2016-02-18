<?php
/**
 * ResponseDataStorage is responsible for storing and managing response
 * data retrieved from the Mollom system for any protected entities.
 */

namespace Drupal\mollom\Storage;

use Drupal\Core\Database\Query\Merge;


class ResponseDataStorage {

  const CONVERT_DATABASE = 0;
  const CONVERT_API = 1;

  /**
   * Save Mollom validation data to the database.
   *
   * Based on the specified entity type and id, this function stores the
   * validation results returned by Mollom in the database.
   *
   * The special $entity type "session" may be used for mails and messages, which
   * originate from form submissions protected by Mollom, and can be reported by
   * anyone; $id is expected to be a Mollom session id instead of an entity id
   * then.
   *
   * @param $data
   *   An object containing Mollom session data for the entity, containing at
   *   least the following properties:
   *   - entity: The entity type of the data to save.
   *   - id: The entity ID the data belongs to.
   *   - form_id: The form ID the session data belongs to.
   *   - session_id: The session ID returned by Mollom.
   *   And optionally:
   *   - spam: A spam check result double returned by Mollom.
   *   - spam_classification: A final spam classification result string; 'ham',
   *     'spam', or 'unsure'.
   *   - quality: A rating of the content's quality, in the range of 0 and 1.0.
   *   - profanity: A profanity check rating returned by Mollom, in the range of
   *     0 and 1.0.
   *   - languages: An array containing language codes the content might be
   *     written in.
   *   - flags_spam: Total count of spam feedback reports.
   *   - flags_ham: Total count of ham feedback reports.
   *   - flags_profanity: Total count of profanity feedback reports.
   *   - flags_quality: Total count of low quality feedback reports.
   *   - flags_unwanted: Total count of unwanted feedback reports.
   */
  public static function save($data) {
    $data->changed = REQUEST_TIME;

    // Convert languages array into a string.
    if (isset($data->languages) && is_array($data->languages)) {
      $languages = array();
      foreach ($data->languages as $language) {
        $languages[] = $language['languageCode'];
      }
      $data->languages = implode(',', $languages);
    }

    $defaults = array(
      'entity' => '',
      'id' => 0,
      'content_id' => '',
      'captcha_id' => '',
      'form_id' => '',
      'changed' => 0,
      'moderate' => 0,
      'spam_score' => 0,
      'spam_classification' => '',
      'solved' => 0,
      'quality_score' => 0,
      'profanity_score' => 0,
      'reason' => '',
      'languages' => '',
      'flags_spam' => 0,
      'flags_ham' => 0,
      'flags_profanity' => 0,
      'flags_quality' => 0,
      'flags_unwanted' => 0,
    );
    $data = array_filter((array) $data, function($value) {
      return $value !== NULL;
    });
    $save_data = (array) self::convertFieldnames($data, self::CONVERT_DATABASE);

    $fields = array_intersect_key($save_data + $defaults, $defaults);

    $result = \Drupal::database()->merge('mollom')
      ->keys(['id' => $data['id'], 'entity' => $data['entity']])
      ->fields($fields)
      ->execute();

    // Pass unconverted data to other modules for backwards compatibility.
    if ($result === Merge::STATUS_INSERT) {
      \Drupal::moduleHandler()->invokeAll('mollom_data_insert', $data);
    }
    else {
      \Drupal::moduleHandler()->invokeAll('mollom_data_update', $data);
    }
    return $data;
  }

  /**
   * Deletes a Mollom session data record from the database.
   *
   * @param $entity
   *   The entity type to delete data for.
   * @param $id
   *   The entity id to delete data for.
   */
  public static function delete($entity, $id) {
    return self::deleteMultiple($entity, array($id));
  }

  /**
   * Deletes multiple Mollom session data records from the database.
   *
   * @param $entity
   *   The entity type to delete data for.
   * @param $ids
   *   An array of entity ids to delete data for.
   */
  public static function deleteMultiple($entity, array $ids) {
    foreach ($ids as $id) {
      $data = self::loadByEntity($entity, $id);
      if ($data) {
        \Drupal::moduleHandler()->invokeAll('mollom_data_delete', array($data));
      }
    }
    return \Drupal::database()->delete('mollom')
      ->condition('entity', $entity)
      ->condition('id', $ids, 'IN')
      ->execute();
  }

  /**
   * Load a Mollom data record by contentId.
   *
   * @param $content_id
   *   The content_id to retrieve data for.
   */
  public static function loadByContent($content_id) {
    $data = \Drupal::database()->select('mollom', 'm')
      ->fields('m')
      ->condition('m.content_id', $content_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    return self::convertFieldnames($data, self::CONVERT_API);
  }

  /**
   * Load a Mollom data record from the database.
   *
   * @param $entity
   *   The entity type to retrieve data for.
   * @param $id
   *   The entity id to retrieve data for.
   */
  public static function loadByEntity($entity, $id) {
    $data = \Drupal::database()->select('mollom', 'm')
      ->fields('m')
      ->condition('m.entity', $entity)
      ->condition('m.id', $id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    return self::convertFieldnames($data, self::CONVERT_API);
  }

  /**
   * Loads the Mollom data records from the database for a specific entity type.
   *
   * @param $entity
   *   The entity type to retrieve data for.
   *
   * @return array
   *   The matching Mollom data as an array keyed by entity id.
   */
  public static function loadByEntityType($type) {
    $data = \Drupal::database()->select('mollom', 'm')
      ->fields('m')
      ->condition('m.entity', $type)
      ->execute()
      ->fetchAllAssoc('id');
    return self::convertFieldnames($data, self::CONVERT_API);
  }

  /**
   * Convert data names either from database to API or API to database.
   *
   * The Mollom API handles certain field names as mixed case.  This is against
   * the practice of using all lower-case database field names.
   *
   * @param \stdClass $data
   *   The data to convert
   * @param $to
   *   Indicates the direction of the conversion.  Use
   *   ResponseDataStorage::CONVERT_API to convert to camelCase or
   *   ResponseDataStorage::CONVERT_DATABASE to convert to lower_case.
   * @return \stdClass
   *   A cloned data object with converted field names.
   */
  protected static function convertFieldnames($data, $to) {
    if (empty($data)) {
      return $data;
    }
    $replace = array(
      'content_id' => 'contentId',
      'captcha_id' => 'captchaId',
      'spam_score' => 'spamScore',
      'spam_classification' => 'spamClassification',
      'quality_score' => 'qualityScore',
      'profanity_score' => 'profanityScore',
    );
    if ($to === self::CONVERT_DATABASE) {
      $replace = array_flip($replace);
    }

    $clone = [];
    foreach($data as $prop => $value) {
      if (array_key_exists($prop, $replace)) {
        $clone[$replace[$prop]] = $value;
      }
      else {
        $clone[$prop] = $value;
      }
    }
    return (object) $clone;
  }
} 
