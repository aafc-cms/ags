<?php

namespace Drupal\prefer_latest_content;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;


class Utils {

  static protected $bodyClasses = array();

  static public function addMessage($message) {
    \Drupal::messenger()->addMessage($message);
  }

  static public function addToLog($message, $DEBUG = FALSE) {
    //$DEBUG = FALSE;
    if ($DEBUG) {
      \Drupal::logger('prefer_latest_content')->notice($message);
    }
  }


  static public function killPageCache() {
    \Drupal::service('page_cache_kill_switch')->trigger();
  }


  static public function getRouteName() {
    $route = \Drupal::routeMatch()->getRouteName();

    return $route;
  }


  static public function goto($url, $statusCode=null, $headers=null, $trusted=false) {
    //
    // Redirect to specific route or URL.
    //
    //return new RedirectResponse(\Drupal::url('user.page'));
    //
    //return new RedirectResponse(\Drupal::url('locale.translate_status', [], ['absolute' => TRUE]));
    //
    //return new RedirectResponse(\Drupal::url('<front>', [], ['absolute' => TRUE]));
    //
    //return new RedirectResponse(Url::fromRoute('system.modules_uninstall')->setAbsolute()->toString());
    //
    //return new RedirectResponse(Url::fromRoute('<current>')->toString());

    $statusCode = $statusCode === null ? 302 : $statusCode;
    $headers = $headers === null ? array() : $headers;

    if ($trusted) {
      $response = new TrustedRedirectResponse($url, $statusCode, $headers);
    }
    else {
      $response = new RedirectResponse($url, $statusCode, $headers);
    }

    $request = \Drupal::request();

    // Save the session so things like messages get saved.
    $request->getSession()->save();
    $response->prepare($request);

    // Make sure to trigger kernel events.
    \Drupal::service('kernel')->terminate($request, $response);

    $response->send();
    exit();
  }


  static public function gotoRoute($key, $statusCode=null, $headers=null, $trusted=false) {
    return static::goto(\Drupal::url($key), $statusCode, $headers, $trusted);
  }


  static public function gotoExternal($url, $statusCode=null, $headers=null, $trusted=false) {
    return static::goto($url, $statusCode, $headers, true);
  }


  static public function getNidFromPath($path = NULL) {
    if (empty($path)) {
      return FALSE;
    }

    $regex = '/[\/]{0,1}(node)\/([0-9]{1,9})/';

    preg_match($regex, $path, $matches, PREG_OFFSET_CAPTURE, 0);

    if (isset($matches[1][0])) {
      $path_type = $matches[1][0];
      if ($path_type == 'node') {
        $nid = $matches[2][0];
        if (isset($matches[2][0])) {
          return $nid;
        }
      }
    }
    return FALSE;
  }


  static public function gotoLegacy($path='', $options=array('code' => 302), $responseCode=null) {
    $query = isset($options['query']) ? $options['query'] : array();
    $language = isset($options['language']) ? $options['language'] : \Drupal::languageManager()->getCurrentLanguage();
    $nid = isset($options['nid']) ? $options['nid'] : NULL;

    if (preg_match('#^[[:alpha:]][[:alnum:]]*://#', $path)) {
      self::addToLog('Redirect using a uri', TRUE);
      $url = Url::fromUri($path, $options);
    }
    else {
      if (empty($nid)) {
        $nid = self::getNidFromPath($path);
      }
      if (empty($nid)) {
        self::addToLog('Redirect using a route ', TRUE);
        $url = Url::fromRoute($path, [], ['language' => $language]);
      }
      else {
        $route = 'entity.node.canonical';
        if (stripos($path, 'latest') > 0) {
          $route = 'entity.node.latest_version';
        }
        self::addToLog('Redirect using using nid', TRUE);
        $url = Url::fromRoute($route, ['node' => $nid], ['language' => $language]);
      }
    }

    return static::goto($url->toString(), $responseCode, array('code'), false);
  }


  static public function watchdog($module, $message, $vars=null, $type=null) {
    static $typeMap = [
      'WATCHDOG_EMERGENCY' => 'emergency',
      'WATCHDOG_ALERT'     => 'alert',
      'WATCHDOG_CRITICAL'  => 'critical',
      'WATCHDOG_ERROR'     => 'error',
      'WATCHDOG_WARNING'   => 'warning',
      'WATCHDOG_NOTICE'    => 'notice',
      'WATCHDOG_INFO'      => 'info',
      'WATCHDOG_DEBUG'     => 'debug',
    ];

    $method = 'notice';
    if (isset($typeMap[(string)$type])) {
      $methd = $typeMap[(string)$type];
    }

    $vars = is_array($vars) ? $vars : array();

    \Drupal::logger($module)->$method($message, $vars);
  }


  static public function isAjaxRequest() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      return true;
    }

    return false;
  }


  static public function getLatestRevisionOnlyIfDraft($nid, &$vid) {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $otherLang = 'fr';
    if ($lang == 'fr') {
      $otherLang = 'en';
    }
    $latestRevisionResult = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->latestRevision()
      ->condition('nid', $nid, '=')
      ->execute();
    if (count($latestRevisionResult)) {
      $node_revision_id = key($latestRevisionResult);
      if ($node_revision_id == $vid) {
        // There is no pending revision, the current revision is the latest.
        return FALSE;
      }
      $vid = $node_revision_id;
      $latestRevision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($node_revision_id);
      if ($latestRevision->language()->getId() != $lang) {
        $latestRevision = $latestRevision->getTranslation($lang);
      }
      $moderation_state = $latestRevision->get('moderation_state')->getString();
      if ($moderation_state == 'draft') {
        return $latestRevision;
      }
    }
    return FALSE;
  }

}
