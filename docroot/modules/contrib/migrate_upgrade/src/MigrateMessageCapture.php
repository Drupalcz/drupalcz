<?php

/**
 * @file
 * Contains \Drupal\migrate_upgrade\MigrateMessageCapture.
 */

namespace Drupal\migrate_upgrade;

use Drupal\migrate\MigrateMessageInterface;

/**
 * Defines a migrate message class for capturing messages rather than
 * displaying them directly.
 */
class MigrateMessageCapture implements MigrateMessageInterface {

  /**
   * Array of recorded messages.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $this->messages[] = $message;
  }

  /**
   * Clear out any captured messages.
   */
  public function clear() {
    $this->messages = [];
  }

  /**
   * Return any captured messages.
   *
   * @return array
   */
  public function getMessages() {
    return $this->messages;
  }

}
