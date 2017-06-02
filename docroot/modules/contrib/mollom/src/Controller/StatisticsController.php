<?php

namespace Drupal\mollom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Template\Attribute;
use Drupal\mollom\Utility\MollomUtilities;

/**
 * Defines a page that shows statistics.
 */
class StatisticsController extends ControllerBase {
  public function content() {

    MollomUtilities::getAdminAPIKeyStatus();

    $config = $this->config('mollom.settings');

    $embed_attributes = array(
      'src' => 'https://mollom.com/statistics.swf?key=' . urlencode($config->get('keys.public')),
      'quality' => 'high',
      'width' => '100%',
      'height' => '430',
      'name' => 'Mollom',
      'align' => 'middle',
      'play' => 'true',
      'loop' => 'false',
      'allowScriptAccess' => 'sameDomain',
      'type' => 'application/x-shockwave-flash',
      'pluginspage' => 'http://www.adobe.com/go/getflashplayer',
      'wmode' => 'transparent'
    );
    return array(
      '#type' => 'markup',
      '#markup' => '<embed' . new Attribute($embed_attributes) . '></embed>',
      '#allowed_tags' => ['embed'],
    );
  }
}
