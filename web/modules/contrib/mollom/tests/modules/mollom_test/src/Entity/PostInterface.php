<?php

namespace Drupal\mollom_test\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface PostInterface defines the interface for mollom test post submissions.
 *
 * @package Drupal\mollom_test\Entity
 */
interface PostInterface extends ContentEntityInterface {

  // Getters and setters
  public function getTitle();
  public function setTitle($title);
  public function getBody();
  public function setBody($body);
  public function getStatus();
  public function setStatus($status);
  public function getStorageRecord();
}
