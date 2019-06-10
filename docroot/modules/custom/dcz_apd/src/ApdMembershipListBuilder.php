<?php

namespace Drupal\dcz_apd;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of APD membership entities.
 *
 * @ingroup dcz_apd
 */
class ApdMembershipListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('APD membership ID');
    $header['profile_id'] = $this->t('Member profile ID');
    $header['valid_from'] = $this->t('Valid since');
    $header['valid_to'] = $this->t('Valid to');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\dcz_apd\Entity\ApdMembership */
    $row['id'] = $entity->id();
    $row['profile_id'] = $entity->getProfileId();
    $row['valid_from'] = $entity->get('valid_from')->value;
    $row['valid_to'] = $entity->get('valid_to')->value;
    return $row + parent::buildRow($entity);
  }

}
