<?php


// Inspired by: https://www.drupal.org/project/bootstrap/issues/2912815
namespace Drupal\grstheme_bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\Breadcrumb as BootstrapBreadcrumb;
use Drupal\agri_admin\AgriAdminHelper;

use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Pre-processes variables for the "breadcrumb" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("breadcrumb")
 */
class Breadcrumb extends BootstrapBreadcrumb {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    $breadcrumb = &$variables['breadcrumb'];
    // Determine if breadcrumbs should be displayed.
    $breadcrumb_visibility = $this->theme->getSetting('breadcrumb');

    if (($breadcrumb_visibility == 0 || ($breadcrumb_visibility == 2 && \Drupal::service('router.admin_context')->isAdminRoute())) || empty($breadcrumb)) {
      $breadcrumb = [];
      return;
    }

    // Remove first occurrence of the "Home" <front> link, provided by core.
    if (!$this->theme->getSetting('breadcrumb_home')) {
      $front = Url::fromRoute('<front>')->toString();
      foreach ($breadcrumb as $key => $link) {
        if (isset($link['url']) && $link['url'] === $front) {
          unset($breadcrumb[$key]);
          break;
        }
      }
    }

    if ($this->theme->getSetting('breadcrumb_title') && !empty($breadcrumb)) {
      $request = \Drupal::request();
      $route_match = \Drupal::routeMatch();
      $page_title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
      $node = \Drupal::routeMatch()->getParameter('node');
      if (isset($node) && !is_string($node) && is_object($node) && method_exists($node, 'getType')) {
        $nodetype= $node->getType();
        if ($nodetype == 'page' || $nodetype == 'landing_page') {
          if (!empty($page_title)) {
            $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
            $nodemenulink = $menu_link_manager->loadLinksByRoute('entity.node.canonical', array('node' => $node->id()));
            $link = array_pop($nodemenulink);
            $linktitle = $page_title; // Safe default value
            if (!empty($link) && is_object($link)) {
              $linktitle = $link->getTitle(); // This gets title in the current language.
            }
            $breadcrumb[] = [
              'text' => $linktitle,
              'attributes' => new Attribute(['class' => ['active']]),
            ];
          }
        }
        else {
          if (!empty($page_title)) {
            $breadcrumb[] = [
              'text' => $page_title,
              'attributes' => new Attribute(['class' => ['active']]),
            ];
          }
        }
      }
      else {
        if (!empty($page_title)) {
          $breadcrumb[] = [
            'text' => $page_title,
            'attributes' => new Attribute(['class' => ['active']]),
          ];
        }
      }
    }

    // Add cache context based on url.
    $variables->addCacheContexts(['url.path', 'languages']);
  }

}

