<?php

namespace Drupal\news_bulletin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class NewsBulletinController extends ControllerBase {
  /**
   * Display the markup.
   *
   * @return array
   */
  public function content() {
    return [
//      '#type' => 'markup',
      '#theme' => 'news_bulletin',
//      '#markup' => $this->t('Hello, World!'),
//      '#attached' => ['library' => ['news_bulletin/bulletins']] // OR add this through the twig template
    ];
  }

  public function getSomething() {
    $keys = array();
  
    if (isset($_GET['type'])) {
      $type = $_GET['type'];
    }
  
    return new JsonResponse(array_merge(array('status' => true), $keys));
  }

}
