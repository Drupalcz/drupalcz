<?php

namespace Drupal\dcz_apd\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\dcz_apd\Entity\ApdMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApdMembershipController.
 *
 *  Returns responses for APD membership routes.
 */
class ApdMembershipController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * ApdMembershipController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer service.
   */
  public function __construct(DateFormatterInterface $dateFormatter, Renderer $renderer) {
    $this->dateFormatter = $dateFormatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a APD membership  revision.
   *
   * @param int $apd_membership_revision
   *   The APD membership  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($apd_membership_revision) {
    $apd_membership = $this->entityTypeManager()
      ->getStorage('apd_membership')
      ->loadRevision($apd_membership_revision);
    $view_builder = $this->entityTypeManager()
      ->getViewBuilder('apd_membership');

    return $view_builder->view($apd_membership);
  }

  /**
   * Page title callback for a APD membership  revision.
   *
   * @param int $apd_membership_revision
   *   The APD membership  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($apd_membership_revision) {
    $apd_membership = $this->entityTypeManager()
      ->getStorage('apd_membership')
      ->loadRevision($apd_membership_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $apd_membership->label(),
      '%date' => $this->dateFormatter->format($apd_membership->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a APD membership .
   *
   * @param \Drupal\dcz_apd\Entity\ApdMembershipInterface $apd_membership
   *   A APD membership  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ApdMembershipInterface $apd_membership) {
    $account = $this->currentUser();
    $langcode = $apd_membership->language()->getId();
    $langname = $apd_membership->language()->getName();
    $languages = $apd_membership->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $apd_membership_storage = $this->entityTypeManager()
      ->getStorage('apd_membership');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $apd_membership->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $apd_membership->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all apd membership revisions") || $account->hasPermission('administer apd membership entities')));
    $delete_permission = (($account->hasPermission("delete all apd membership revisions") || $account->hasPermission('administer apd membership entities')));

    $rows = [];

    $vids = $apd_membership_storage->revisionIds($apd_membership);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\dcz_apd\ApdMembershipInterface $revision */
      $revision = $apd_membership_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
        ->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter
          ->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $apd_membership->getRevisionId()) {
          $link = $this->getLinkGenerator()->generate($date, new Url('entity.apd_membership.revision', [
            'apd_membership' => $apd_membership->id(),
            'apd_membership_revision' => $vid,
          ]));
        }
        else {
          $link = $apd_membership->toLink($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.apd_membership.revision_revert', [
                'apd_membership' => $apd_membership->id(),
                'apd_membership_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.apd_membership.revision_delete', [
                'apd_membership' => $apd_membership->id(),
                'apd_membership_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['apd_membership_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}