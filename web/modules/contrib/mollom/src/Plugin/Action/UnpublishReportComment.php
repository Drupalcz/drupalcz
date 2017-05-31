<?php

namespace Drupal\mollom\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\mollom\API\FeedbackManager;
use Drupal\mollom\EntityReportAccessManager;

/**
 * Unpublishes a comment and reports to Mollom.
 *
 * @Action(
 *   id = "mollom_comment_unpublish_action",
 *   label = @Translation("Report to Mollom and unpublish"),
 *   type = "comment"
 * )
 */
class UnpublishReportComment extends ActionBase {

  /**
   * {@inheritdoc}
   * @param $comment \Drupal\comment\CommentInterface
   */
  public function execute($comment = NULL) {
    if (empty($comment)) {
      return;
    }
    FeedbackManager::sendFeedback('comment', $comment->id(), 'spam', 'moderate', 'mollom_action_unpublish_comment');

    $comment->setPublished(FALSE);
    $comment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\comment\CommentInterface $object */

    $node = $object->getCommentedEntity();
    $form_id = 'comment_' . $node->getEntityTypeId() . '_' . $node->bundle() . '_form';
    $result = EntityReportAccessManager::accessReport($object, $form_id, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
