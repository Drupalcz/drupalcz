<?php

/**
 * @file
 * Controller routines for views-based XML.
 */

namespace Drupal\juicebox\Controller;

use Drupal\juicebox\JuiceboxGalleryInterface;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;


/**
 * Controller routines for field-based XML.
 */
class JuiceboxXmlControllerViewsStyle extends JuiceboxXmlControllerBase {

  /**
   * The view machine name for the view involved in this XML request.
   *
   * @var string
   */
  protected $viewName;

  /**
   * The view display name for the view involved in this XML request.
   *
   * @var string
   */
  protected $displayName;

  /**
   * The loaded view involved in this XML request.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * An indexed array of view args that apply to the view used in this XML
   * request.
   *
   * @var array
   */
  protected $viewArgs = array();


  /**
   * {@inheritdoc}
   */
  protected function init() {
    $attribs = $this->request->attributes->get('_raw_variables');
    // Set data sources as properties.
    $this->viewName = $attribs->get('viewName');
    $this->displayName = $attribs->get('displayName');
    $this->viewArgs = $this->queryToArgs();
    // Load the view itself.
    $this->view = Views::getView($this->viewName);
    if (is_object($this->view) && $this->view instanceof ViewExecutable) {
      // All looks good.
      return;
    }
    throw new \Exception(t('Cannot instantiate view-based Juicebox gallery as no view can be loaded.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function access() {
    return $this->view->access($this->displayName);
  }

  /**
   * {@inheritdoc}
   */
  protected function getGallery() {
    $this->view->setDisplay($this->displayName);
    $this->view->setArguments($this->viewArgs);
    $rendered_view = $this->view->render();
    // Make sure that the Juicebox is actually built.
    if (!empty($rendered_view['#rows']['#gallery']) && $rendered_view['#rows']['#gallery'] instanceof JuiceboxGalleryInterface && $rendered_view['#rows']['#gallery']->getId()) {
      return $rendered_view['#rows']['#gallery'];
    }
    throw new \Exception(t('Cannot build Juicebox XML for view-based gallery.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function calculateXmlCacheTags() {
    // Pull the tags directly out of the view object.
    return $this->view->getCacheTags();
  }

  /**
   * Utility to extract the current set of view args from query params.
   *
   * @return array
   *   A indexed array of the view args.
   */
  protected function queryToArgs() {
    $args = array();
    // Get the args from the query params.
    $query_args = $this->request->query->all();
    foreach($query_args as $param => $value) {
      if (preg_match('/^arg_[0-9]+$/', $param)) {
        list($prefix, $key) = explode('_', $param);
        $args[$key] = $value;
      }
    }
    return $args;
  }

}
