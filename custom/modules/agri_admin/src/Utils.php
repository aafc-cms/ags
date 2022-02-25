<?php

namespace Drupal\agri_admin;

use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Utility\UrlHelper;

/**
 * Utility class for agrisource.
 */
class Utils {

  /**
   * The body classes.
   *
   * @var array
   */
  static protected $bodyClasses = [];

  /**
   * Wrap Drupal 8/9 api to retrieve configuration group.
   */
  public static function configGet($group, $readOnly = TRUE) {
    if ($readOnly) {
      return \Drupal::config($group);
    }

    return \Drupal::configFactory()->getEditable($group);
  }

  /**
   * Alias for configGet().
   */
  public static function configGetRead($group) {
    return static::configGet($group, TRUE);
  }

  /**
   * Another alias for configGet().
   */
  public static function configGetWrite($group) {
    return static::configGet($group, TRUE);
  }

  /**
   * Wrapper for retrieving a single item from a config group.
   */
  public static function variableGet($group, $key, $default = NULL) {
    return \Drupal::config($group)->get($key, $default);
  }

  /**
   * Wrapper for storing a single item from a config group.
   */
  public static function variableSet($group, $key, $value) {
    \Drupal::configFactory()->getEditable($group)->set($key, $value)->save();
  }

  /**
   * When you're wanting to blast some cache, try this.
   */
  public static function killPageCache() {
    \Drupal::service('page_cache_kill_switch')->trigger();
  }

  /**
   * Retrieve the var in _GET.
   */
  public static function getVarGet($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGet($_GET, $name, $default, $defaultOnNull);
  }

  /**
   * Retrieve the var in _GET (trimmed).
   */
  public static function getVarGetTrimmed($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGetTrimmed($_GET, $name, $default, $defaultOnNull);
  }

  /**
   * Is the var set in _GET.
   */
  public static function getVarIsSet($name) {
    return static::vpathIsSet($_GET, $name);
  }

  /**
   * Does the var exist in _GET.
   */
  public static function getVarExists($name) {
    return static::vpathExists($_GET, $name);
  }

  /**
   * Retrieve a variable from a _POST request.
   */
  public static function postVarGet($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGet($_POST, $name, $default, $defaultOnNull);
  }

  /**
   * Retrieve a variable from a _POST request (trimmed).
   */
  public static function postVarGetTrimmed($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGetTrimmed($_POST, $name, $default, $defaultOnNull);
  }

  /**
   * Is the var set in _POST.
   */
  public static function postVarIsSet($name) {
    return static::vpathIsSet($_POST, $name);
  }

  /**
   * Determine if _POST var exists.
   */
  public static function postVarExists($name) {
    return static::vpathExists($_POST, $name);
  }

  /**
   * Determine if _REQUEST var exists.
   */
  public static function requestVarGet($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGet($_REQUEST, $name, $default, $defaultOnNull);
  }

  /**
   * Retrieve a variable from a _REQUEST (trimmed).
   */
  public static function requestVarGetTrimmed($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGetTrimmed($_REQUEST, $name, $default, $defaultOnNull);
  }

  /**
   * Determine if var is set from a _REQUEST.
   */
  public static function requestVarIsSet($name) {
    return static::vpathIsSet($_REQUEST, $name);
  }

  /**
   * Determine if var exists from a _REQUEST.
   */
  public static function requestVarExists($name) {
    return static::vpathExists($_REQUEST, $name);
  }

  /**
   * Retrieve a variable from a _COOKIE.
   */
  public static function cookieVarGet($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGet($_COOKIE, $name, $default, $defaultOnNull);
  }

  /**
   * Retrieve a variable from a _COOKIE (trimmed).
   */
  public static function cookieVarGetTrimmed($name, $default = NULL, $defaultOnNull = FALSE) {
    return static::vpathGetTrimmed($_COOKIE, $name, $default, $defaultOnNull);
  }

  /**
   * Is a variable from a _COOKIE set?
   */
  public static function cookieVarIsSet($name) {
    return static::vpathIsSet($_COOKIE, $name);
  }

  /**
   * Does a variable from a _COOKIE exist?
   */
  public static function cookieVarExists($name) {
    return static::vpathExists($_COOKIE, $name);
  }

  /**
   * Retrieve the current route name from Drupals API (symfony)
   */
  public static function getRouteName() {
    $route = \Drupal::routeMatch()->getRouteName();

    return $route;
  }

  /**
   * A drupal 7 familiar way to redirect to another url.
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
   * Redirect to a specific route using the Drupal API (symfony).
   */
  public static function gotoRoute($key, $statusCode = NULL, $headers = NULL, $trusted = FALSE) {
    return static::goto(\Drupal::url($key), $statusCode, $headers, $trusted);
  }

  /**
   * Redirect to an external url using the Drupal API (symfony).
   */
  public static function gotoExternal($url, $statusCode = NULL, $headers = NULL, $trusted = FALSE) {
    return static::goto($url, $statusCode, $headers, TRUE);
  }

  /**
   * Retrieve the nid from the current path if possible.
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
   * A drupal 7 familiar way to redirect to another url made to act like it.
   */
  public static function gotoLegacy($path = '', $options = [], $responseCode = NULL) {
    $query = isset($options['query']) ? $options['query'] : [];
    $language = isset($options['language']) ? $options['language'] : \Drupal::languageManager()->getCurrentLanguage();
    $nid = isset($options['nid']) ? $options['nid'] : NULL;

    if (preg_match('#^[[:alpha:]][[:alnum:]]*://#', $path)) {
      AgriAdminHelper::addToLog('Redirect using a uri'/*, TRUE*/);
      $url = Url::fromUri($path, $options);
    }
    else {
      if (empty($nid)) {
        $nid = self::getNidFromPath($path);
      }
      if (empty($nid)) {
        AgriAdminHelper::addToLog('Redirect using a route '/*, TRUE*/);
        $url = Url::fromRoute($path, [], ['language' => $language]);
      }
      else {
        AgriAdminHelper::addToLog('Redirect using using nid'/*, TRUE*/);
        $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], ['language' => $language]);
      }
    }

    return static::goto($url->toString(), $responseCode, [], FALSE);
  }

  /**
   * Write to the dblog.
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
   * Add classes into an array for the body element.
   */
  public static function addBodyClasses($classes) {
    $classes = (array) $classes;

    foreach ($classes as $class) {
      if (($class = trim($class)) !== '') {
        static::$bodyClasses[$class] = $class;
      }
    }
  }

  /**
   * Add a class into an array for the body element.
   */
  public static function addBodyClass($class) {
    static::addBodyClasses($class);
  }

  /**
   * Retrieve the body classes from an array.
   */
  public static function getBodyClasses() {
    return static::$bodyClasses;
  }

  /**
   * Determine if the current request is an ajax request or not.
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
   * Process a renderArray into html.
   *
   * @param array $renderArray
   *   The render array.
   * @param bool $root
   *   Not sure what this parameter is.
   *
   * @return mixed
   *   Returns bool or \Drupal\Component\Render\MarkupInterface (the rendered HTML).
   */
  public static function render(array $renderArray, bool $root = TRUE) {
    if (is_array($renderArray) || is_object($renderArray)) {
      if ($renderArray) {
        if ($root) {
          $rendered = \Drupal::service('renderer')->renderRoot($renderArray);
        }
        else {
          $rendered = \Drupal::service('renderer')->render($renderArray);
        }
      }
      else {
        $rendered = '';
      }
    }
    else {
      $rendered = (string) $renderArray;
    }

    return $rendered;
  }

  /**
   * Protect some markup.
   */
  public static function markupProtect($html) {
    $markup = Markup::create($html);

    return $markup;
  }

  /**
   * Protect some markup.
   */
  public static function protectMarkup($html) {
    return static::markupProtect($html);
  }

  /**
   * Retrieve the request token from the Drupal api csrfToken.
   */
  public static function getRequestToken($name = '') {
    $token = \Drupal::csrfToken()->get($name);

    return $token;
  }

  /**
   * Is the request token valid?
   */
  public static function checkRequestToken($token, $name = '') {
    $status = FALSE;

    if (static::getUser()->isAnonymous()) {
      $status = TRUE;
    }
    else {
      $valid = static::getRequestToken($name);

      if ($valid === $token) {
        $status = TRUE;
      }
    }

    return $status;
  }

  /**
   * Retrieve the current user using the Drupa API.
   */
  public static function getUser() {
    return \Drupal::currentUser();
  }

  /**
   * Verify if the current access unpublished hashtoken is found.
   */
  public static function checkUrlToken($formbody, &$foundtokenhref, &$tokenmsgarray) {
    if (is_null($foundtokenhref) || empty($foundtokenhref)) {
      // Makes this function PHP 8.0 compatible.
      $foundtokenhref = FALSE;
    }
    $regex_token = '/\?auHash=/';
    preg_match_all($regex_token, $formbody, $matches, PREG_SET_ORDER);
    foreach ($matches as $match_token) {
      $tokenhref = reset($match_token);
      if (isset($tokenhref) && strlen($tokenhref) == 8) {
        if (!$foundtokenhref) {
          $foundtokenhref = TRUE;
        }
      }
    }
    return $foundtokenhref;
  }

  /**
   * Verify if the page is published or not.
   */
  public static function checkPagePublished($formbody, &$foundunpublishingnode, &$publish_page_message_array) {
    if (is_null($foundunpublishingnode) || empty($foundunpublishingnode)) {
      // Makes this function PHP 8.0 compatible.
      $foundunpublishingnode = FALSE;
    }
    $regex_mediaobj = "/data-entity-type=\"node\" data-entity-uuid=\"([a-z]|[0-9]){8}-(([0-9]|[a-z]){4}-){3}([0-9]|[a-z]){12}\"/";
    $regex_uuid = "/([a-z]|[0-9]){8}-(([0-9]|[a-z]){4}-){3}([0-9]|[a-z]){12}/";
    preg_match_all($regex_mediaobj, $formbody, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $node = reset($match);
      preg_match_all($regex_uuid, $node, $id_match, PREG_SET_ORDER);
      $nodeuuid = $id_match[0][0];
      $nentity = \Drupal::service('entity.repository')->loadEntityByUuid('node', $nodeuuid);
      $nodeid = $nentity->id();
      $nmstate = $nentity->get('moderation_state')->getValue();
      $nmstatetmp = array_pop($nmstate);
      $nmstateStr = array_pop($nmstatetmp);
      if ($nmstateStr != 'published') {
        if (!$foundunpublishingnode) {
          $foundunpublishingnode = TRUE;
        }
        $msg = '';
        $msg = '<ul><li>' . $node . " /node/" . $nodeid . '</li></ul>';
        array_push($publishpagemsgarray, $msg);
      }
    }
    return $foundunpublishingnode;
  }

  /**
   * Verify if the url in the form body is absolute or not.
   *
   * Returns (bool)
   *   True or false.
   */
  public static function checkAbsoluteUrl($formbody, &$found_absolute_url, &$publish_page_message_array) {
    if (is_null($found_absolute_url) || empty($found_absolute_url)) {
      // Makes this function PHP 8.0 compatible.
      $found_absolute_url = FALSE;
    }
    $regex_tokenabspath = '/href=\".*\"/m';
    preg_match_all($regex_tokenabspath, $formbody, $pathmatches, PREG_SET_ORDER);
    foreach ($pathmatches as $match) {
      $hrefpath = reset($match);
      // Ignore the token case.
      $tokenhead = "?auHash=";
      if (strpos($hrefpath, $tokenhead) == FALSE) {
        $path = str_replace("href=\"", "", $hrefpath);
        $path = str_replace("\"", "", $path);
        if (UrlHelper::isExternal($path) && UrlHelper::isValid($path, TRUE)) {
          if (UrlHelper::externalIsLocal($path, \Drupal::request()->getSchemeAndHttpHost())) {
            $absolutepathstring = $path;
            $base_path = \Drupal::request()->getBasePath();
            $host = parse_url($path, PHP_URL_HOST);
            $host_end = strpos($path, $host) + strlen($host) + strlen($base_path);
            $path = substr($path, $host_end);
            $path = urldecode(trim($path, '/'));
            $path_args = explode('/', $path);
            $prefix = array_shift($path_args);
            $path = '/' . implode('/', $path_args);
            $nodpath = \Drupal::service('path_alias.manager')->getPathByAlias($path, $prefix);
            if (preg_match('/node\/(\d+)/', $nodpath, $matches)) {
              $hrefnode = Node::load($matches[1]);
              if (isset($hrefnode)) {
                if (!$found_absolute_url) {
                  $foundabsoluteurl = TRUE;
                }
                $msg = '';
                $msg = '<ul><li>' . $absolutepathstring . '</li></ul>';
                array_push($publishpagemsgarray, $msg);
              }
            }
          }
        }
      }
    }
    return $found_absolute_url;
  }

  /**
   * Retrieve the title for a specific menu link uri.
   */
  public static function getMenuItemTitle($link_uri, $langcode = 'en') {
    $query = \Drupal::database()->select('menu_link_content_data', 'ml_tbl')
      ->fields('ml_tbl', ['title'])
      ->condition('link__uri', $link_uri)
      ->condition('langcode', $langcode)
      ->condition('menu_name', 'main');
    $result = $query->execute()->fetchField();
    return $result;
  }

}
