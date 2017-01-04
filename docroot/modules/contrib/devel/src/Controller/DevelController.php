<?php

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for devel module routes.
 */
class DevelController extends ControllerBase {

  /**
   * Clears all caches, then redirects to the previous page.
   */
  public function cacheClear() {
    drupal_flush_all_caches();
    drupal_set_message('Cache cleared.');
    return $this->redirect('<front>');
  }

  /**
   * Returns a dump of a route object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   A render array containing the route object.
   */
  public function menuItem(Request $request, RouteMatchInterface $route_match) {
    $output = [];

    // Get the route object from the path query string if available.
    if ($path = $request->query->get('path')) {
      try {
        /* @var \Symfony\Cmf\Component\Routing\ChainRouter $router */
        $router = \Drupal::service('router');
        $route = $router->match($path);
        $output['route'] = ['#markup' => kpr($route, TRUE)];
      }
      catch (\Exception $e) {
        drupal_set_message($this->t("Unable to load route for url '%url'", ['%url' => $path]), 'warning');
      }
    }
    // No path specified, get the current route.
    else {
      $route = $route_match->getRouteObject();
      $output['route'] = ['#markup' => kpr($route, TRUE)];
    }

    return $output;
  }

  public function themeRegistry() {
    $hooks = theme_get_registry();
    ksort($hooks);
    return array('#markup' => kprint_r($hooks, TRUE));
  }

  /**
   * Builds the elements info overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function elementsPage() {
    $element_info_manager = \Drupal::service('element_info');

    $elements_info = array();
    foreach ($element_info_manager->getDefinitions() as $element_type => $definition) {
      $elements_info[$element_type] = $definition + $element_info_manager->getInfo($element_type);
    }

    ksort($elements_info);

    return array('#markup' => kdevel_print_object($elements_info));
  }

  /**
   * Builds the fields info overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function fieldInfoPage() {
    $fields = FieldStorageConfig::loadMultiple();
    ksort($fields);
    $output['fields'] = array('#markup' => kprint_r($fields, TRUE, $this->t('Fields')));

    $field_instances = FieldConfig::loadMultiple();
    ksort($field_instances);
    $output['instances'] = array('#markup' => kprint_r($field_instances, TRUE, $this->t('Instances')));

    $bundles = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    ksort($bundles);
    $output['bundles'] = array('#markup' => kprint_r($bundles, TRUE, $this->t('Bundles')));

    $field_types = \Drupal::service('plugin.manager.field.field_type')->getUiDefinitions();
    ksort($field_types);
    $output['field_types'] = array('#markup' => kprint_r($field_types, TRUE, $this->t('Field types')));

    $formatter_types = \Drupal::service('plugin.manager.field.formatter')->getDefinitions();
    ksort($formatter_types);
    $output['formatter_types'] = array('#markup' => kprint_r($formatter_types, TRUE, $this->t('Formatter types')));

    $widget_types = \Drupal::service('plugin.manager.field.widget')->getDefinitions();
    ksort($widget_types);
    $output['widget_types'] = array('#markup' => kprint_r($widget_types, TRUE, $this->t('Widget types')));

    return $output;
  }

  /**
   * Builds the entity types overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityInfoPage() {
    $types = $this->entityTypeManager()->getDefinitions();
    ksort($types);
    return array('#markup' => kprint_r($types, TRUE));
  }

  /**
   * Builds the state variable overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function stateSystemPage() {
    $output['#attached']['library'][] = 'system/drupal.system.modules';

    $output['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );

    $output['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter state name'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '.devel-state-list',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the state name to filter by.'),
      ),
    );

    $can_edit = $this->currentUser()->hasPermission('administer site configuration');

    $header = array(
      'name' => $this->t('Name'),
      'value' => $this->t('Value'),
    );

    if ($can_edit) {
      $header['edit'] = $this->t('Operations');
    }

    $rows = array();
    // State class doesn't have getAll method so we get all states from the
    // KeyValueStorage.
    foreach ($this->keyValue('state')->getAll() as $state_name => $state) {
      $rows[$state_name] = array(
        'name' => array(
          'data' => $state_name,
          'class' => 'table-filter-text-source',
        ),
        'value' => array(
          'data' => kprint_r($state, TRUE),
        ),
      );

      if ($can_edit) {
        $operations['edit'] = array(
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('devel.system_state_edit', array('state_name' => $state_name)),
        );
        $rows[$state_name]['edit'] = array(
          'data' => array('#type' => 'operations', '#links' => $operations),
        );
      }
    }

    $output['states'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No state variables found.'),
      '#attributes' => array(
        'class' => array('devel-state-list'),
      ),
    );

    return $output;
  }

  /**
   * Builds the session overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function session() {
    $output['description'] = array(
      '#markup' => '<p>' . $this->t('Here are the contents of your $_SESSION variable.') . '</p>',
    );
    $output['session'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Session name'), $this->t('Session ID')),
      '#rows' => array(array(session_name(), session_id())),
      '#empty' => $this->t('No session available.'),
    );
    $output['data'] = array(
      '#markup' => kprint_r($_SESSION, TRUE),
    );

    return $output;
  }

}
