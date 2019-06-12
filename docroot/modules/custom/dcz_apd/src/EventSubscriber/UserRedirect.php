<?php

namespace Drupal\dcz_apd\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Class UserRedirect.
 */
class UserRedirect extends HttpExceptionSubscriberBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new UserRedirect object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandledFormats() {
    return ['html'];
  }

  /**
   * {@inheritdoc}
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $route = $event->getRequest()->attributes->get('_route');
    if (($route === 'entity.apd_membership.add_form') && ($this->currentUser->isAnonymous())) {
      $url = Url::fromRoute('user.login', [], [
        'query' => [
          'destination' => $event->getRequest()
            ->getRequestUri(),
        ],
      ]);
      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);
    }
  }

}
