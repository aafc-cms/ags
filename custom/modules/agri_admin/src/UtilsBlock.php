<?php

namespace Drupal\agri_admin;


class UtilsBlock {

  static public function getRenderArray($id, $overrides=null) {
    static $defaults = [
      'provider'      => 'agri_admin',
      'label'         => null,
      'label_display' => 'visible',
    ];

    $blockManager = \Drupal::service('plugin.manager.block');

    // We need a clean instance to get the default config options.
    $pluginBlock = $blockManager->createInstance($id);
    $pluginBlock->setConfiguration([]);

    // Make sure we have defaults because sometimes we don't :/
    $options = $pluginBlock->getConfiguration() + $defaults;

    // Override defaults with any given overrides.
    $options = (array)$overrides + $options;

    // Generate a configured instance.
    $pluginBlock = $blockManager->createInstance($id, $options);

    // Some blocks might implement access check.
    $accessResult = $pluginBlock->access(\Drupal::currentUser());

    // Return empty render array if user doesn't have access.
    // $accessResult can be boolean or an AccessResult class
    if ((is_object($accessResult) && $accessResult->isForbidden())
        ||
        (is_bool($accessResult) && !$accessResult)) {

      // @todo: Do we need cache tags/contexts?
      return [];
    }

    $renderArray = $pluginBlock->build();

    if (!isset($renderArray['content'])) {
      // @todo: Do we need cache tags/contexts?
      return [];
    }

    // We need these extra params to render the block like a content block
    // because Drupal 8, while flexible, makes things difficult for people
    // comming from Drupal 7.
    $renderArray['#theme'] = 'block';
    $renderArray['#configuration'] = $options;
    $renderArray['#plugin_id'] = $id;
    $renderArray['#base_plugin_id'] = $id;
    $renderArray['#derivative_plugin_id'] = '';

    if (isset($options['plugin_id'])) {
      $renderArray['plugin_id'] = $options['plugin_id'];
    }

    if (isset($options['base_plugin_id'])) {
      $renderArray['base_plugin_id'] = $options['base_plugin_id'];
    }

    if (isset($options['derivative_plugin_id'])) {
      $renderArray['derivative_plugin_id'] = $options['derivative_plugin_id'];
    }

    if (!isset($renderArray['#configuration']['label']) || $renderArray['#configuration']['label'] === '') {
      if (isset($renderArray['#title'])) {
        $renderArray['#configuration']['label'] = $renderArray['#title'];
      }
    }

    return $renderArray;
  }


  static public function getRendered($id, $options=null) {
    $rendered = false;

    if (($renderArray = static::getRenderArray($id, $options))) {
      $rendered = Utils::render($renderArray);
    }

    return $rendered;
  }
}
