<?php

namespace Drupal\dcz_apd;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

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
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\dcz_apd\Entity\ApdMembership */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.apd_membership.edit_form',
      ['apd_membership' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
