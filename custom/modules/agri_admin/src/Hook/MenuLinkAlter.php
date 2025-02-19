<?php

namespace Drupal\agri_admin\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implements hook_menu_links_discovered_alter() using OOP approach.
 */
class MenuLinkAlter {

  #[Hook('menu_links_discovered_alter')]
  public function adjustMenuLinkWeight(array &$links) {
    // Check if the specific menu link exists before attempting to modify it.
    if (isset($links['moderation_dashboard.settings'])) {
      // Set the desired weight for the menu link.
      $links['moderation_dashboard.settings']['weight'] = 999;
    }
  }

}

