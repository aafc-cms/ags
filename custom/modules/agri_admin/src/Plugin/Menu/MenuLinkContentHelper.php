<?php

namespace Drupal\agri_admin\Plugin\Menu;

use \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use \Drupal\Core\Menu\MenuLinkBase;
use \Drupal\Core\Entity\EntityStorageInterface

/**
 * Provides the menu link plugin for content menu links.
 */
class MenuLinkContentHelper extends MenuLinkContent {

  /**
   * Gets the language of the menu link.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @return string
   *   The language id.
   */
  public function getLanguage() {
    // We only need to get the title from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->get('langcode')->value;
    }
    return $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $entity = $this->getEntity();
    if (1 || $update) {
      $DEBUG = TRUE;
      if ($DEBUG && $fp = fopen('debug.txt', 'a'))
      {
        $id = $entity->id();
        fwrite($fp, 'debug=ID=' . $id . ' ' . print_r(' test', TRUE) . "\n");
        //fwrite($fp, 'debug=status=' . $nid .print_r(get_object_vars($row->_entity), TRUE));
        //fwrite($fp, 'debug=object_vars=' . $nid .print_r(get_object_vars($row->_entity), TRUE));
        //fwrite($fp, 'debug row methods='.print_r(get_class_methods($variables['row']), TRUE));
        //fwrite($fp, 'debug entity methods='.print_r(get_class_methods($row->_entity), TRUE) . "\n");
        fclose($fp);
      }
    }
  }

  /**
   * Retrieve the languages from translations of the menu link.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @return string
   *   The language ids.
   */
  public function getTranslationLanguages() {
    return $this->getEntity()->getTranslationLanguages();
  }


}
