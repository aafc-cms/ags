<?php

namespace Drupal\wxt_overrides\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for WxT Overrides routes.
 */
class WxtOverridesController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
