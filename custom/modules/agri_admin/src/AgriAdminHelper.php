<?php

namespace Drupal\agri_admin;

//use Drupal\something\AgriUtils;
use Drupal\node\Entity\Node;


class AgriAdminHelper {

  static public function addMessage($message) {
    \Drupal::messenger()->addMessage($message);
  }


  static public function addToLog($message) {
    $DEBUG = TRUE;
    $DEBUG = FALSE;
    if ($DEBUG) {
      \Drupal::logger('agri_admin')->notice($message);
    }
  }


  static public function postCreateOrUpdateAutoTranslate($action, $entity_id, $bundle) {
    if ($bundle == 'page') {
      static::translateLinkIfNotTranslated($entity_id, 'sidebar');
    }

    if ($bundle == 'page' || $bundle == 'landing_page') {
      static::translateLinkIfNotTranslated($entity_id, 'main');
    }
  }


  static public function postUpdateProcess($action, $entity_id, $bundle) {
    static::addToLog(__function__);
    static::addToLog($action . ' entity_id ' . $entity_id);
    $parentUuid = NULL;
    $parentUuidClean = NULL;
    if ($bundle == 'page') {
      $parentNid = static::findParentOfNid($entity_id, 'main', $parentUuid, $parentUuidClean);
      if ($parentNid > 0) {
        $resultCode = static::createChildOfParentNid($entity_id, 'sidebar', $parentNid, $parentUuid);
        if ($resultCode) {
          static::addToLog('Successfully created new sidebar link for nid=id=' . $entity_id);
        }
      }
    }
  }


  static public function getLang() {
    static::addToLog(__function__ . \Drupal::languageManager()->getCurrentLanguage()->getId());
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
    static::addToLog(__function__);
    $menuLink = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties([
        'link.uri' => 'entity:node/' . $nid,
        'menu_name' => $menu_name,
      ]);
    $menuLink = reset($menuLink);
    if (isset($menuLink) && !empty($menuLink)) {
      static::addToLog('Menu Link Exists,<pre>id=' . print_r($menuLink->id(), TRUE) . '</pre>');
      return TRUE;
    }
    static::addToLog('Sidebar menu link for nid does not yet exist: nid=<pre>' . print_r($nid, TRUE) . '</pre>');
    return FALSE;
  }


  static public function getMenuUuidFromNidAndMenuName($nid, $menu_name) {
    static::addToLog(__function__);
    static::addToLog('Search for menu id from nid:' . $nid);
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    $link = $storage->loadByProperties([
      'link.uri' => 'entity:node/' . $nid,
      'menu_name' => $menu_name,
    ]);
    $link = reset($link);
    if (isset($link) && !empty($link)) {
      static::addToLog('<pre>' . print_r(get_class_methods($link), TRUE) . '</pre>');
      static::addToLog('Menu link found from nid=' . $nid . ' uuid = ' . $link->uuid());
      return $link->uuid();
    }
    static::addToLog('Menu link was not found from nid=' . $nid . ' and menu_name = ' . $menu_name);
    return FALSE;
  }


  static public function getMenuIdFromUuid($uuid) {
    static::addToLog(__function__);
    $cleanUuid = str_replace('menu_link_content:', '', $uuid);
    static::addToLog('Search for menu id from uuid clean:' . $cleanUuid);
    $database = \Drupal::database();
    $sql = "SELECT id FROM menu_link_content WHERE uuid = :uuid";
    $result = $database->query($sql, [':uuid' => $cleanUuid]);
    if ($result) {
      while ($row = $result->fetchAssoc()) {
        // $row['column']
        static::addToLog('Found menu id=' . $row['id']);
        return $row['id'];
      }
    }
    static::addToLog('Menu was not found from uuid=' . $cleanUuid);
    return FALSE;
  }


  static public function createChildOfParentNid($nid, $menu_name = 'sidebar', $parentNid, $parentUuid) {
    static::addToLog(__function__);
    // Load main navigation menu link for nid, find the parent nid, then look up the menu link
    // in the sidebar with that nid, that will be the parent of this new sidebar link.

    $node = Node::load($nid);
    $lang = static::getLang();
    if ($lang == 'en') {
      if (!static::menuLinkExists($nid, $menu_name)) {
        $title = $node->getTitle();

        $parentUuid = static::getMenuUuidFromNidAndMenuName($parentNid, $menu_name);
        //$parentId = static::getMenuIdFromUuid($parentUuid);
        $menu_link = \Drupal\menu_link_content\Entity\MenuLinkContent::create([
          'title' => $title,
          'link' => ['uri' => 'entity:node/' . $nid],
          'menu_name' => $menu_name,
          'expanded' => true,
          'langcode' => $lang,
          'status' => TRUE,
          'parent' => 'menu_link_content:' . $parentUuid,
        ]);
        $menu_link->save();
        if (!$menu_link->hasTranslation('fr')) {
          $title = $node->getTranslation('fr')->getTitle();
          $menu_link->addTranslation('fr', ['title' => $title]);
        }
        return $menu_link->save();
      }
    }
    return FALSE;
  }

  static public function translateLinkIfNotTranslated($nid, $menu_name = 'main') {
    $menu = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties(['menu_name' => $menu_name]);

    foreach ($menu as $item) {
      $otherLang = static::getOtherLang();
      if (!$item->hasTranslation($otherLang)) {
        $node = Node::load($nid);
        $title = $node->getTranslation($otherLang)->getTitle();
        $item->addTranslation($otherLang, ['title' => $title]);
      }
      $item->save();
    }
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
          if (isset($parentLink) && !empty($parentLink) && 'entity.node.canonical' == $parentLink->getUrlObject()->getRouteName()) {
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
