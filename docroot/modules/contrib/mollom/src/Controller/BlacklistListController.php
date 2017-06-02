<?php

namespace Drupal\mollom\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\mollom\Storage\BlacklistStorage;
use Drupal\mollom\Utility\MollomUtilities;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Responsible for listing the current site's blacklist entries.
 */
class BlacklistListController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  protected $link;

  /**
   * Class constructor.
   *
   * @param TranslationInterface $translation_manager
   */
  public function __construct(TranslationInterface $translation_manager, LinkGeneratorInterface $link_generator) {
    $this->stringTranslation = $translation_manager;
    $this->link = $link_generator;
  }

  /**
   * Implements ContainerInjectionInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('link_generator')
    );
  }

  /**
   * The content callback for the Blacklist list of current entries.
   *
   * @param string $type
   *   A particular list type to show (based on the entry 'reason').
   */
  function content($type = NULL) {
    MollomUtilities::getAdminAPIKeyStatus();
    MollomUtilities::displayMollomTestModeWarning();

    $items = BlacklistStorage::getList($type);
    $rows = array();

    // Edit/delete.
    $header = array();
    if (empty($type)) {
      $header['type'] = $this->t('List');
    }
    $header['context'] = $this->t('Context');
    $header['matches'] = $this->t('Matches');
    $header['value'] = $this->t('Value');
    $header['operations'] = $this->t('Operations');
    foreach ($items as $entry) {
      $data = array(
        $entry['context'],
        $entry['match'],
        $entry['value'],
        array(
          'data' => array(
            '#type' => 'operations',
            '#links' => array(
              array(
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('mollom.blacklist.delete', array('entry_id' => $entry['id']))
              ),
            ),
          ),
        ),
      );
      if (empty($type)) {
        array_unshift($data, $entry['reason']);
      };
      $rows[] = $data;
    }
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no entries in the blacklist.'),
      '#attributes' => array( 'id' => 'mollom-blacklist-list'),
    );

    return $build;
  }
}
