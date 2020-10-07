<?php

namespace Drupal\grstheme_bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

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
class Breadcrumb extends PreprocessBase implements PreprocessInterface {

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
      if (isset($node)) { 
        $nodetype= $node->getType();
        if ($nodetype != 'page' && $nodetype != 'landing_page') { 
          if (!empty($page_title)) {
            $breadcrumb[] = [
              'text' => $page_title,
              'attributes' => new Attribute(['class' => ['active']]),
            ];
          }
        }
        else{
          $nodeid = $node ->id();
          $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
          $nodemenulink = $menu_link_manager->loadLinksByRoute('entity.node.canonical', array('node' => $nodeid));
          $link = array_pop($nodemenulink);
          $ldefinition = $link->getPluginDefinition();
          $linktitle = $ldefinition['title'];
          if (!empty($linktitle)) {
            $breadcrumb[] = [
              'text' => $linktitle,
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
    $variables->addCacheContexts(['url']);
  }

}
