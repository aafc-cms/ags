<?php

//use Drupal\something\AgriUtils;
use Drupal\node\Entity\Node;
namespace Drupal\agri_admin;

class AgriAdminHelper {

  static public function addMessage($message) {
    \Drupal::messenger()->addMessage($message);
  }


  static public function addToLog($message) {
    $DEBUG = FALSE;
    //$DEBUG = TRUE;
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
    static::addToLog(__function__ . '()=' . \Drupal::languageManager()->getCurrentLanguage()->getId());
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


  static public function legacyMenuLinkExists($menu_name, $ts_nid, $lang) {
    static::addToLog(__function__ . '(' . $menu_name . ', ' . $ts_nid . ', ' . $lang . ')');
    $database = \Drupal::database();
    $sql = "SELECT uuid FROM menu_link_content WHERE ts_nid = :tsnid and langcode = :lang";
    $result = $database->query($sql, [':tsnid' => $ts_nid, ':lang' => $lang]);
    $uuid = '';
    if ($result) {
      while ($row = $result->fetchAssoc()) {
        // $row['column']
        static::addToLog('Found menu uuid=' . $row['uuid']);
        $uuid = $row['uuid'];
        return $uuid;
      }
    }
    static::addToLog('Menu was not found from ts_nid=' . $ts_nid . ' lang=' . $lang);
    return FALSE;
  }


  static public function menuExternalLinkExists($title, $external_link, $menu_name = 'main', $ts_nid, $lang) {
    static::addToLog(__function__);

    $uuid = static::legacyMenuLinkExists($menu_name, $ts_nid, $lang);
    if (!empty($uuid) && !gettype($uuid) == 'boolean') {
      return $uuid;
    }

    if (empty($title) || empty($external_link)) {
      static::addToLog('Menu Link Exists , title or external_link is empty, cannot process, abort.');
      return TRUE;
    }
    $menuLink = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties([
        'link.title' => $title,
        'link.uri' => $external_link,
        'menu_name' => $menu_name,
      ]);
    $menuLink = reset($menuLink);
    if (isset($menuLink) && !empty($menuLink)) {
      static::addToLog('Menu Link Exists,<pre>uuid=' . print_r($menuLink->uuid(), TRUE) . '</pre>');
      return $menuLink->uuid();
    }
    static::addToLog($menu_name . ' menu link for ts_nid does not yet exist: ts_nid=<pre>' . print_r($ts_nid, TRUE) . '</pre>');
    return FALSE;
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


  static public function getUuidFromId($id, $lang) {
    static::addToLog(__function__ . ' lang=' . $lang);
    $database = \Drupal::database();
    $sql = "SELECT uuid FROM menu_link_content WHERE id = :id and langcode = :lang";
    $result = $database->query($sql, [':id' => $id, 'lang' => $lang]);
    if ($result) {
      while ($row = $result->fetchAssoc()) {
        // $row['column']
        static::addToLog('Found ' . $lang . ' menu uuid=' . $row['uuid']);
        return $row['uuid'];
      }
    }
    static::addToLog('Menu was not found from uuid=' . $cleanUuid);
    return FALSE;
  }


  static public function updateDcrId($menuid, $dcr_id, $lang) {
    // For teamsite import organising.
    $database = \Drupal::database();
    $num_updated = $database->update('menu_link_content')
    ->fields([
      'dcr_id' => $dcr_id,
    ])
    ->condition('id', $menuid, '=')
    ->condition('langcode', $lang, '=')
    ->execute();
  }


  static public function updateTsPnid($menuid, $ts_pnid, $lang) {
    // For teamsite import organising.
    $database = \Drupal::database();
    $num_updated = $database->update('menu_link_content')
    ->fields([
      'ts_pnid' => $ts_pnid,
    ])
    ->condition('id', $menuid, '=')
    ->condition('langcode', $lang, '=')
    ->execute();
  }


  static public function updateTsNid($menuid, $ts_nid, $lang) {
    // For teamsite import organising.
    $database = \Drupal::database();
    $num_updated = $database->update('menu_link_content')
    ->fields([
      'ts_nid' => $ts_nid,
    ])
    ->condition('id', $menuid, '=')
    ->condition('langcode', $lang, '=')
    ->execute();
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


  static public function createExternalLegacyMenuLink($title, $external_link, $menu_name = 'main', $ts_nid, $ts_pnid, $titleFr, $external_linkFr, $lang = 'en') {
    static::addToLog(__function__ . ' : ' . $lang);
    // Load main navigation menu link for nid, find the parent nid, then look up the menu link
    // in the sidebar with that nid, that will be the parent of this new sidebar link.

    //$lang = static::getLang();
    //$lang = 'en'; // default to 'en' for now.
    if ($lang == 'en') {
      if (!static::menuExternalLinkExists($title, $external_link, $menu_name, $ts_nid, $lang)) {
        $parentUuid = static::legacyMenuLinkExists($menu_name, $ts_pnid, $lang);
        $menu_attributes = [
          'title' => $title,
          'link' => ['uri' => $external_link],
          'menu_name' => $menu_name,
          'target' => '_blank',
          'external' => TRUE,
          'parent' => 'menu_link_content:' . $parentUuid,
          'expanded' => true,
          'bundle' => 'menu_link_content',
          'status' => TRUE,
          'langcode' => $lang,
        ];
        //static::addToLog('en menu_attributes["parent"]=' . $menu_attributes['parent']);
        if (gettype($parentUuid) == 'boolean') {
          unset($menu_attributes['parent']);
        }
        $menu_link = \Drupal\menu_link_content\Entity\MenuLinkContent::create($menu_attributes);
        $returnCode = $menu_link->save();
        if ($returnCode) {
          $id = $menu_link->id();
          $uuid = static::getUuidFromId($id, $lang);
          static::updateTsNid($id, $ts_nid, $lang);
          static::updateTsPnid($id, $ts_pnid, $lang);
          //static::updateDcrId($id, $dcr_id, $lang); // dcr_id is not numeric for external legacy sitemap menu links
        }
        if (!$menu_link->hasTranslation('fr') && $external_link == $external_linkFr) {
          $menu_link->addTranslation('fr', ['title' => $titleFr]);
          static::addToLog('JOSEPH TEST ********************************************* JOSEPH TEST ************English');
          $returnCode = $menu_link->save();
          /*if ($returnCode) {
            // Not sure if we need to do this here, translated items I don't know if they put a content entry in?
            //@TODO revisit this .
            $id = $menu_link->id();
            $uuid = static::getUuidFromId($id, $lang);
            static::updateTsNid($id, $ts_nid, $lang);
            static::updateTsPnid($id, $ts_pnid, $lang);
          }*/
        }
        if ($returnCode) {
          static::addToLog('JOSEPH TEST ********************************************* JOSEPH TEST ************English new uuid:' . $uuid);
          return $uuid;
        }
        return $returnCode; //@TODO remove this
      }
    }
    else if ($lang == 'fr' && ($external_link != $external_linkFr)) {
      if (!static::menuExternalLinkExists($titleFr, $external_linkFr, $menu_name, $ts_nid, $lang)) {
        $parentUuid = static::legacyMenuLinkExists($menu_name, $ts_pnid, $lang);
        $menu_attributes = [
          'title' => $titleFr,
          'link' => ['uri' => $external_linkFr],
          'menu_name' => $menu_name,
          'expanded' => true,
          'bundle' => 'menu_link_content',
          'langcode' => $lang,
          'status' => TRUE,
          'parent' => 'menu_link_content:' . $parentUuid,
        ];
        if (gettype($parentUuid) == 'boolean') {
          unset($menu_attributes['parent']);
        }
        static::addToLog('JOSEPH TEST ********************************************* JOSEPH TEST ************French');
        $menu_link = \Drupal\menu_link_content\Entity\MenuLinkContent::create($menu_attributes);
        $returnCode = $menu_link->save();
        if ($returnCode) {
          $id = $menu_link->id();
          $uuid = static::getUuidFromId($id, $lang);
          static::updateTsNid($id, $ts_nid, $lang);
          static::updateTsPnid($id, $ts_pnid, $lang);
          //static::updateDcrId($id, $dcr_id, $lang); // dcr_id is not numeric for external legacy sitemap menu links
          static::addToLog('JOSEPH TEST ********************************************* JOSEPH TEST ************English new uuid:' . $uuid);
        }
        if ($returnCode) {
          $uuid = static::getUuidFromId($id, $lang);
          return $uuid;
        }
        return $returnCode; //@TODO remove this
      }
    }
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
