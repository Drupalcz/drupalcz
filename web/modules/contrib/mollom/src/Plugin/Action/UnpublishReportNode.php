<?php

namespace Drupal\mollom\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\mollom\API\FeedbackManager;
use Drupal\mollom\EntityReportAccessManager;

/**
 * Unpublishes a node and reports to Mollom.
 *
 * @Action(
 *   id = "mollom_node_unpublish_action",
 *   label = @Translation("Report to Mollom and unpublish"),
 *   type = "node"
 * )
 */
class UnpublishReportNode extends ActionBase {

  /**
   * {@inheritdoc}
   * @param $node \Drupal\node\NodeInterface
   */
  public function execute($node = NULL) {
    if (empty($node)) {
      return;
    }
    FeedbackManager::sendFeedback('node', $node->id(), 'spam', 'moderate', 'mollom_action_unpublish_node');

    $node->setPublished(FALSE);
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $form_id = 'node_' . $object->bundle() . '_form';
    $result = EntityReportAccessManager::accessReport($object, $form_id, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
