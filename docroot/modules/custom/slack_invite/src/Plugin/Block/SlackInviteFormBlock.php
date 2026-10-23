<?php

/**
 * @file
 * Contains \Drupal\slack_invite\Plugin\Block\SlackInviteFormBlock.
 */

namespace Drupal\slack_invite\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Slack Invite form' block.
 *
 * @Block(
 *   id = "slack_invite_form_block",
 *   admin_label = @Translation("Slack Invite Form"),
 *   category = @Translation("Forms")
 * )
 */
class SlackInviteFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\slack_invite\Form\SlackInviteForm');
  }

}
