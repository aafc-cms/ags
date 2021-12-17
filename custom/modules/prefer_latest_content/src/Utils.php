<?php

namespace Drupal\prefer_latest_content;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Utility class used by prefer_latest_content module.
 */
class Utils {

  /**
   * Add a message using the Drupal api.
   */
  public static function addMessage($message) {
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Add a log message using the Drupal api.
   */
  public static function addToLog($message, $debug = FALSE) {
    if ($debug) {
      \Drupal::logger('prefer_latest_content')->notice($message);
    }
  }

  /**
   * Retrieve the current route name using the Drupal api (symfony).
   */
  public static function getRouteName() {
    $route = \Drupal::routeMatch()->getRouteName();

    return $route;
  }

  /**
   * Wrapper for the Drupal 8/9 redirect api to be easier like the Drupal 7 api.
   */
  public static function goto($url, $statusCode = NULL, $headers = NULL, $trusted = FALSE) {
    //
    // Redirect to specific route or URL.
    //
    // return new RedirectResponse(\Drupal::url('user.page'));
    //
    // return new RedirectResponse(\Drupal::url('locale.translate_status', [], ['absolute' => TRUE]));
    //
    // return new RedirectResponse(\Drupal::url('<front>', [], ['absolute' => TRUE]));
    //
    // return new RedirectResponse(Url::fromRoute('system.modules_uninstall')->setAbsolute()->toString());
    //
    // return new RedirectResponse(Url::fromRoute('<current>')->toString());
    $statusCode = $statusCode === NULL ? 302 : $statusCode;
    $headers = $headers === NULL ? [] : $headers;

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

  /**
   * Wrapper for the goto method for an internal route.
   */
  public static function gotoRoute($key, $statusCode = NULL, $headers = NULL, $trusted = FALSE) {
    return static::goto(\Drupal::url($key), $statusCode, $headers, $trusted);
  }

  /**
   * Wrapper for the goto method for external redirect.
   */
  public static function gotoExternal($url, $statusCode = NULL, $headers = NULL, $trusted = FALSE) {
    return static::goto($url, $statusCode, $headers, TRUE);
  }

  /**
   * Retrieve the nid from the path parameter.
   *
   * $path (string)
   *   Path contains a string like /en/example/2022-01-01
   */
  public static function getNidFromPath($path = NULL) {
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

  /**
   * A wrapper for the goto() method that is to act similar to Drupal 7s goto().
   */
  public static function gotoLegacy($path = '', $options = ['code' => 302], $responseCode = NULL) {
    $query = isset($options['query']) ? $options['query'] : [];
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

    return static::goto($url->toString(), $responseCode, ['code'], FALSE);
  }

  /**
   * Log something to the dblog similar to how Drupal 7 watchdog function works.
   */
  public static function watchdog($module, $message, $vars = NULL, $type = NULL) {
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
    if (isset($typeMap[(string) $type])) {
      $methd = $typeMap[(string) $type];
    }

    $vars = is_array($vars) ? $vars : [];

    \Drupal::logger($module)->$method($message, $vars);
  }

  /**
   * Is the current request an ajax request?
   *
   * Return (bool).
   */
  public static function isAjaxRequest() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Retrieve the latest revision if it is a draft.
   *
   * $nid (int)
   *   Node id.
   * $vid (int)
   *   Revision id by reference.
   */
  public static function getLatestRevisionOnlyIfDraft($nid, &$vid) {
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
