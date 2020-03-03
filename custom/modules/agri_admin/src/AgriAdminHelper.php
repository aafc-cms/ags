<?php

namespace Drupal\agri_admin;

//use Drupal\something\AgriUtils;
use Drupal\node\Entity\Node;


class AgriAdminHelper {

  static public function addMessage($message) {
    \Drupal::messenger()->addMessage($message);
  }


  static public function addToLog($message) {
    \Drupal::logger('agri_admin')->notice($message);
  }


  static public function postUpdateProcess($action, $entity_id, $bundle) {
    static::addToLog('test DEBUG');
    static::addToLog($action . ' entity_id ' . $entity_id);
    $parentUuid = NULL;
    $parentUuidClean = NULL;
    if ($bundle == 'page') {
      $parentNid = static::findParentOfNid($entity_id, 'main', $parentUuid, $parentUuidClean);
      if ($parentNid > 0) {
        $resultCode = static::createChildOfNid($entity_id, 'sidebar', $parentNid, $parentUuid);
        if ($resultCode) {
          static::addToLog('Successfully created new sidebar link for nid=id=' . $entity_id);
        }
      }
    }
  }


  static public function getLang() {
    return \Drupal::languageManager()->getCurrentLanguage()->getId();
  }


  static public function getOtherLang() {
    // Get the list of all languages
    $langcode = static::getLang();
    $otherLangCode = 'fr';
    if ($langcode != 'en') {
      $otherLangCode = 'en';
    }
    return $otherLangCode;
  }


  static public function menuLinkExists($nid, $menu_name = 'sidebar') {
    $menu = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties([
        'link.uri' => 'entity:node/' . $nid,
        'menu_name' => $menu_name,
      ]);
    $parentLink = reset($parentLink);
    if (isset($parentLink)) {
      static::addToLog('Menu Link Exists,<pre>id=' . print_r($parentLink->id(), TRUE) . '</pre>');
      return TRUE;
    }
    static::addToLog('Sidebar menu link for nid does not yet exist: nid=<pre>' . print_r($nid, TRUE) . '</pre>');
    return FALSE;
  }

  static public function createChildOfNid($nid, $menu_name = 'sidebar', $parentNid, $parentUuid) {
    // Load main navigation menu link for nid, find the parent nid, then look up the menu link
    // in the sidebar with that nid, that will be the parent of this new sidebar link.

    $node = Node::load($nid);
    $lang = static::getLang();
    if ($lang == 'en') {
      if (!static::menuLinkExists($nid, $menu_name)) {
        $title = $node->getTitle();

        $menu_link = \Drupal\menu_link_content\Entity\MenuLinkContent::create([
          'title' => $title,
          'link' => ['uri' => 'entity:node/' . $nid],
          'menu_name' => $menu_name,
          'expanded' => true,
          'langcode' => $lang,
          'status' => TRUE,
          'parent' => $parentUuid,
        ]);
        return $menu_link->save();
      }
    }
    return FALSE;
  }


  static public function findParentOfNid($nid, $menu_name = 'main', &$parentUuid, &$parentUuidClean) {
    static::addToLog(__function__);
    $parentMenuLink = NULL;

    $menu = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties(['menu_name' => $menu_name]);

    $linkNodeId = '';
    foreach ($menu as $item) {
      if (!$item->getUrlObject()->isExternal()) {
        if ('entity.node.canonical' == $item->getUrlObject()->getRouteName()) {
          $params = $item->getUrlObject()->getRouteParameters();
          $linkNodeId = $params['node'];
        } else {
          $linkNodeId = '';
        }

        if ($linkNodeId == $nid) {
          static::addToLog('<pre>nid=' . print_r($nid, TRUE) . '</pre>');
          $parentMenuLinkId = $item->getParentId();
          static::addToLog('<pre>parentId=' . print_r($parentMenuLinkId, TRUE) . '</pre>');
          $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
          $newUuid = str_replace('menu_link_content:', '', $parentMenuLinkId);
          $parentLink = $storage->loadByProperties([
            'uuid' => $newUuid,
          ]);
          $parentLink = reset($parentLink);
          if ('entity.node.canonical' == $parentLink->getUrlObject()->getRouteName()) {
            $parentParams = $parentLink->getUrlObject()->getRouteParameters();
            $parentLinkNodeId = $parentParams['node'];
            if (is_numeric($parentLinkNodeId)) {
              $parentUuid = $parentMenuLinkId;
              $parentUuidClean = $newUuid;
              return $parentLinkNodeId;
            }
          } else {
            $parentLinkNodeId = '';
          }
        }
      }
    } // End of foreach.
    return FALSE;
  }

}
