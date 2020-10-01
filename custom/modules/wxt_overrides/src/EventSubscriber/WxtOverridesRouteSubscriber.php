<?php

namespace Drupal\wxt_overrides\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * WxT Overrides route subscriber.
 */
class WxtOverridesRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Replace "some.route.name" below with the actual route you want to override.
    if ($route = $collection->get('system.404')) {
      $route->setDefaults(array(
        '_controller' => '\Drupal\wxt_overrides\Controller\System4xxOverride::on404',
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }

}
