<?php

namespace Drupal\dcz_apd\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for APD membership entities.
 */
class ApdMembershipViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
