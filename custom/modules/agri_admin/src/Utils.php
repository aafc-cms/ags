<?php

namespace Drupal\agri_admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Utility\UrlHelper;


class Utils {

  static protected $bodyClasses = array();


  static public function configGet($group, $readOnly=true) {
    if ($readOnly) {
      return \Drupal::config($group);
    }

    return \Drupal::configFactory()->getEditable($group);
  }


  static public function configGetRead($group) {
    return static::configGet($group, true);
  }


  static public function configGetWrite($group) {
    return static::configGet($group, true);
  }


  static public function variableGet($group, $key, $default=null) {
    return \Drupal::config($group)->get($key, $default);
  }


  static public function variableSet($group, $key, $value) {
    \Drupal::configFactory()->getEditable($group)->set($key, $value)->save();
  }


  static public function killPageCache() {
    \Drupal::service('page_cache_kill_switch')->trigger();
  }


  static public function getVarGet($name, $default=null, $defaultOnNull=false) {
    return static::vpathGet($_GET, $name, $default, $defaultOnNull);
  }


  static public function getVarGetTrimmed($name, $default=null, $defaultOnNull=false) {
    return static::vpathGetTrimmed($_GET, $name, $default, $defaultOnNull);
  }


  static public function getVarIsSet($name) {
    return static::vpathIsSet($_GET, $name);
  }


  static public function getVarExists($name) {
    return static::vpathExists($_GET, $name);
  }


  static public function postVarGet($name, $default=null, $defaultOnNull=false) {
    return static::vpathGet($_POST, $name, $default, $defaultOnNull);
  }


  static public function postVarGetTrimmed($name, $default=null, $defaultOnNull=false) {
    return static::vpathGetTrimmed($_POST, $name, $default, $defaultOnNull);
  }


  static public function postVarIsSet($name) {
    return static::vpathIsSet($_POST, $name);
  }


  static public function postVarExists($name) {
    return static::vpathExists($_POST, $name);
  }


  static public function requestVarGet($name, $default=null, $defaultOnNull=false) {
    return static::vpathGet($_REQUEST, $name, $default, $defaultOnNull);
  }


  static public function requestVarGetTrimmed($name, $default=null, $defaultOnNull=false) {
    return static::vpathGetTrimmed($_REQUEST, $name, $default, $defaultOnNull);
  }


  static public function requestVarIsSet($name) {
    return static::vpathIsSet($_REQUEST, $name);
  }


  static public function requestVarExists($name) {
    return static::vpathExists($_REQUEST, $name);
  }


  static public function cookieVarGet($name, $default=null, $defaultOnNull=false) {
    return static::vpathGet($_COOKIE, $name, $default, $defaultOnNull);
  }


  static public function cookieVarGetTrimmed($name, $default=null, $defaultOnNull=false) {
    return static::vpathGetTrimmed($_COOKIE, $name, $default, $defaultOnNull);
  }


  static public function cookieVarIsSet($name) {
    return static::vpathIsSet($_COOKIE, $name);
  }


  static public function cookieVarExists($name) {
    return static::vpathExists($_COOKIE, $name);
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


  static public function gotoLegacy($path='', $options=array(), $responseCode=null) {
    $query = isset($options['query']) ? $options['query'] : array();
    $language = isset($options['language']) ? $options['language'] : \Drupal::languageManager()->getCurrentLanguage();
    $nid = isset($options['nid']) ? $options['nid'] : NULL;

    if (preg_match('#^[[:alpha:]][[:alnum:]]*://#', $path)) {
      \Drupal\agri_admin\AgriAdminHelper::addToLog('Redirect using a uri'/*, TRUE*/);
      $url = Url::fromUri($path, $options);
    }
    else {
      if (empty($nid)) {
        $nid = self::getNidFromPath($path);
      }
      if (empty($nid)) {
        \Drupal\agri_admin\AgriAdminHelper::addToLog('Redirect using a route '/*, TRUE*/);
        $url = Url::fromRoute($path, [], ['language' => $language]);
      }
      else {
        \Drupal\agri_admin\AgriAdminHelper::addToLog('Redirect using using nid'/*, TRUE*/);
        $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], ['language' => $language]);
      }
    }

    return static::goto($url->toString(), $responseCode, array(), false);
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


  static public function addBodyClasses($classes) {
    $classes = (array)$classes;

    foreach ($classes as $class) {
      if (($class = trim($class)) !== '') {
        static::$bodyClasses[$class] = $class;
      }
    }
  }


  static public function addBodyClass($class) {
    static::addBodyClasses($class);
  }


  static public function getBodyClasses() {
    return static::$bodyClasses;
  }


  static public function isAjaxRequest() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      return true;
    }

    return false;
  }


  static public function render($renderArray, $root=true) {
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
      $rendered = (string)$renderArray;
    }

    return $rendered;
  }


  static public function markupProtect($html) {
    $markup = \Drupal\Core\Render\Markup::create($html);

    return $markup;
  }


  static public function protectMarkup($html) {
    return static::markupProtect($html);
  }


  static public function getRequestToken($name='') {
    $token = \Drupal::csrfToken()->get($name);

    return $token;
  }


  static public function checkRequestToken($token, $name='') {
    $status = false;

    if (static::getUser()->isAnonymous()) {
      $status = true;
    }
    else {
      $valid = static::getRequestToken($name);

      if ($valid === $token) {
        $status = true;
      }
    }

    return $status;
  }


  static public function getUser() {
    return \Drupal::currentUser();
  }


  static public function checkUrlToken($formbody, &$foundtokenhref = FALSE, &$tokenmsgarray) {
    $regex_token = '/href=\".*\?auHash=([a-zA-Z]|[0-9]|[_]|[-]){43}\"/m';
    preg_match_all( $regex_token, $formbody , $matches,PREG_SET_ORDER);
    foreach ( $matches as $match_token) {
      $tokenhref = reset($match_token);
      if (isset ($tokenhref) && strlen($tokenhref) >20) {
        if (!$foundtokenhref) {
          $foundtokenhref = TRUE;
        }
        $msg = '';
        $msg = '<ul><li>' . $tokenhref . '</li></ul>';
        array_push($tokenmsgarray, $msg);
      }
    }
    return $foundtokenhref;
  }


  static public function checkPagePublished($formbody, &$foundunpublishingnode  = FALSE, &$publishpagemsgarray) {
    $regex_mediaobj = "/data-entity-type=\"node\" data-entity-uuid=\"([a-z]|[0-9]){8}-(([0-9]|[a-z]){4}-){3}([0-9]|[a-z]){12}\"/";
    $regex_uuid = "/([a-z]|[0-9]){8}-(([0-9]|[a-z]){4}-){3}([0-9]|[a-z]){12}/";
    preg_match_all( $regex_mediaobj, $formbody , $matches, PREG_SET_ORDER);
    foreach ( $matches as $match) {
      $node = reset($match);
      preg_match_all( $regex_uuid, $node, $id_match, PREG_SET_ORDER);
      $nodeuuid = $id_match[0][0];
      $nentity = \Drupal::service('entity.repository')->loadEntityByUuid('node', $nodeuuid);
      $nodeid = $nentity->id();
      $nmstate = $nentity->get('moderation_state')->getValue();
      $nmstatetmp = array_pop($nmstate);
      $nmstateStr = array_pop($nmstatetmp);
      if ($nmstateStr != 'published') {
        if (!$foundunpublishingnode ) {
          $foundunpublishingnode = TRUE;
        }
        $msg = '';
        $msg = '<ul><li>' . $node . " /node/" . $nodeid . '</li></ul>';
        array_push($publishpagemsgarray, $msg);
      }
    }
    return $foundunpublishingnode ;
  }


  static public function checkAbsoluteUrl($formbody, &$foundabsoluteurl  = FALSE, &$publishpagemsgarray) {
    $regex_tokenabspath = '/href=\".*\"/m';
    preg_match_all( $regex_tokenabspath, $formbody , $pathmatches, PREG_SET_ORDER);
    foreach ( $pathmatches as $match) {
      $hrefpath = reset($match);
      //ignore the token case
      $tokenhead = "?auHash=";
      if (strpos($hrefpath, $tokenhead) == false ) {
        $path = str_replace("href=\"", "",$hrefpath);
        $path = str_replace("\"", "",$path);
        if (UrlHelper::isExternal($path) && UrlHelper::isValid($path, TRUE)) {
          if (UrlHelper::externalIsLocal($path, \Drupal::request()->getSchemeAndHttpHost())) {
            $absolutepathstring = $path;
            $host = parse_url($path, PHP_URL_HOST);
            $host_end = strpos($path, $host) + strlen($host);
            $path = substr($path, $host_end);
            $path = urldecode(trim($path, '/'));
            $path_args = explode('/', $path);
            $prefix = array_shift($path_args);
            $path = '/' . implode('/', $path_args);
            $nodpath = \Drupal::service('path.alias_manager')->getPathByAlias($path);
            if(preg_match('/node\/(\d+)/', $nodpath, $matches)) {
              $hrefnode = \Drupal\node\Entity\Node::load($matches[1]);
              if (isset ($hrefnode)) {
                if (!$foundabsoluteurl ) {
                  $foundabsoluteurl = TRUE;
                }
                $msg = '';
                $msg = '<ul><li>'  . $absolutepathstring . '</li></ul>';
                array_push($publishpagemsgarray, $msg);
              }
            }
          }
        }
      }
    }
    return $foundabsoluteurl ;
  }
}