<?php

namespace Drupal\mollom\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\mollom\Utility\Logger;

class Subscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // Set a low value to start as early as possible.
    $events[KernelEvents::REQUEST][] = array('onRequest', -100);

    // Why only large positive value works here?
    $events[KernelEvents::TERMINATE][] = array('onTerminate', 1000);

    return $events;
  }

  /**
   * Implements hook_init().
   */
  function onRequest() {
    // On all Mollom administration pages, check the module configuration and
    // display the corresponding requirements error, if invalid.
    $url = Url::fromRoute('<current>');
    $current_path = $url->toString();
    if (empty($_POST) && strpos($current_path, 'admin/config/content/mollom') === 0 && \Drupal::currentUser()->hasPermission('administer mollom')) {
      // Re-check the status on the settings form only.
      $status = \Drupal\mollom\Utility\MollomUtilities::getAPIKeyStatus($current_path == 'admin/config/content/mollom/settings');
      if ($status !== TRUE) {
        // Fetch and display requirements error message, without re-checking.
        module_load_install('mollom');
        $requirements = mollom_requirements('runtime', FALSE);
        if (isset($requirements['mollom']['description'])) {
          drupal_set_message($requirements['mollom']['description'], 'error');
        }
      }
    }
  }

  /**
   * Implements after all other processing.
   */
  function onTerminate() {
    Logger::writeLog();
  }


}
