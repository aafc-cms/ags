<?php

namespace Drupal\news_bulletin\Controller;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\views\Views;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\mysql\Driver\Database\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\path_alias\PathAliasStorage;

/**
 * Controller for the news bulletin.
 */
class NewsBulletinEmailController extends ControllerBase {

  /**
   * The temp store of values.
   *
   * @var Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * The database connection.
   *
   * @var Drupal\mysql\Driver\Database\mysql\Connection
   */
  protected $database;

  /**
   * The path alias storage object.
   *
   * @var Drupal\path_alias\PathAliasStorage
   */
  protected $aliasStorage;

  /**
   * The image being processed width.
   *
   * @var string
   */
  protected $tempWidth;

  /**
   * The image being processed height.
   *
   * @var string
   */
  protected $tempHeight;

  /**
   * Pass the dependency to the object constructor.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, Connection $database, PathAliasStorage $aliasStorage) {
    $this->database = $database;
    // For "news_bulletin," any unique namespace will do.
    $this->tempStore = $temp_store_factory->get('news_bulletin');
    $this->aliasStorage = $aliasStorage;
    $this->tempWidth = 0;
    $this->tempHeight = 0;
  }

  /**
   * Uses Symfony's ContainerInterface to declare dependency to be passed to constructor.
   */
  public static function create(ContainerInterface $container) {
    $etm = $container->get('entity_type.manager');
    return new static(
      $container->get('tempstore.private'),
      $container->get('database'),
      $etm->getStorage('path_alias')
    );
  }

  /**
   * Read some temporary data.
   */
  public function getTypeWeights() {
    $news_type_weights = $this->tempStore->get('news_type_weights');
    if (!isset($news_type_weights)) {
      $news_type_weights = [];
    }
    return $news_type_weights;
    // Do other stuff, return a render array, etc...
  }

  /**
   * Read some temporary data.
   */
  public function getNewsNids() {
    $news_nids_selected = $this->tempStore->get('news_nids');
    if (!isset($news_nids_selected)) {
      $news_nids_selected = [];
    }
    return $news_nids_selected;
  }

  /**
   * Display the markup.
   *
   * @return array
   *   Theme values for the templates, variables for twig.
   */
  public function content() {
    $request = \Drupal::request();
    $referer = $request->headers->get('referer');
    if (isset($referer) && strpos($referer, 'outside-ncr') == FALSE) {
      return [
        '#theme' => 'news_bulletin_email',
        '#news_types' => $this->getNewsTypes(),
        '#news_items' => $this->reOrderNodeWeights('en', FALSE),
        '#types_by_weight' => $this->reOrderTypeWeights('en', FALSE),
        '#news_types_fr' => $this->getNewsTypes('fr'),
        '#news_items_fr' => $this->reOrderNodeWeights('fr', FALSE),
        '#types_by_weight_fr' => $this->reOrderTypeWeights('fr', FALSE),
        '#vars_array' => $this->options(),
      ];
    }
    else {
      return [
        '#theme' => 'news_bulletin_email',
        '#news_types' => $this->getNewsTypes(),
        '#news_items' => $this->reOrderNodeWeights('en', TRUE),
        '#types_by_weight' => $this->reOrderTypeWeights('en', TRUE),
        '#news_types_fr' => $this->getNewsTypes('fr'),
        '#news_items_fr' => $this->reOrderNodeWeights('fr', TRUE),
        '#types_by_weight_fr' => $this->reOrderTypeWeights('fr', TRUE),
        '#vars_array' => $this->options(),
      ];
    }
  }

  /**
   * Retrieve the base_path options.
   *
   * @return array
   *   A keyed array but it should be renamed to absolute base path of request (review this later).
   */
  public function options() {
    $options = [];
    $base_path = \Drupal::request()->getBasePath();
    if (empty($base_path)) {
      $options['base_url'] = \Drupal::request()->getSchemeAndHttpHost();
    }
    else {
      if (stripos($base_path, '/') < 0) {
        $options['base_url'] = \Drupal::request()->getSchemeAndHttpHost() . '/' . $base_path;
      }
      else {
        $options['base_url'] = \Drupal::request()->getSchemeAndHttpHost() . $base_path;
      }
    }
    return $options;
  }

  /**
   * Retrieve the sorted news items.
   *
   * @param string $lang
   *   Whether this is 'en' or 'fr'.
   * @param bool $outside
   *   Whether this is for outside or inside.
   *
   * @return array
   *   The news items sorted with array keys for the values.
   */
  public function getNewsItems($lang = 'en', $outside = FALSE) {
    $narrow = 0;
    $view = Views::getView('newsatworkbulletin');

    $view->setDisplay('rest_export_1');
    $view->preExecute();
    $view->execute();

    // $myresults = $view->preview();  = array
    // $myresults = $view->render(); // = array
    // $myresults = $view->result; // = array
    $custom_results = [];
    foreach ($view->result as $id => $result) {
      $node = $result->_entity;
      $nid = $node->id();
      if (!in_array($nid, $this->getNewsNids()) && !empty($this->getNewsNids())) {
        continue;
      }
      if ($node->hasTranslation($lang)) {
        $node = $node->getTranslation($lang);
      }
      $newstype_id = $node->get('field_newstype')->target_id;
      $term = $node->get('field_newstype')->entity;
      $term = $term->getTranslation($lang);
      $termweight = $term->getWeight();
      $summary = $node->get('body')->summary;
      $summary_length = strlen($summary);
      if ($summary_length < 20) {
        $summary = $node->get('body')->value;
        $summary = str_replace('&nbsp;', ' ', $summary);
        $summary = strip_tags($summary);
        $summary_length = strlen($summary);
      }
      $max = 220;
      if ($summary_length >= $max) {
        if (!isset($summary)) {
          $summary = '';
        }
        $max = strpos($summary, ' ', $max);
        $summary = substr($summary, 0, $max) . ' ...';
      }
      $new_item = TRUE;
      $to = $node->get('field_to')->value;
      $title = $node->get('title')->value;
      if (($to == 'ncr' || $to == 'all') && !$outside) {
        foreach ($custom_results as $key_test => $value) {
          // Distinct workaround, view is outputting duplicate nodes.
          if (isset($custom_results[$key_test]['nid'])) {
            if ($custom_results[$key_test]['nid'] == $nid) {
              $new_item = FALSE;
            }
          }
        }
        if ($new_item) {
          $custom_results[$id]['newstype'] = $node->get('field_newstype')->entity->getName();
          $custom_results[$id]['term_id'] = $node->get('field_newstype')->target_id;
          $custom_results[$id]['summary'] = $summary;
          $custom_results[$id]['from'] = $node->get('field_from')->value;
          $custom_results[$id]['nid'] = $node->id();
          $custom_results[$id]['href'] = $this->getUrlForNode($node, $lang);
          $custom_results[$id]['image'] = $this->convertBodyToImg($node, $narrow);
          $custom_results[$id]['image_narrow'] = 0;
          if ($narrow) {
            $custom_results[$id]['image_narrow'] = 1;
          }
          $custom_results[$id]['title'] = $node->getTitle();
          $custom_results[$id]['weight'] = $termweight;
        }
      }
      else {
        if (($to == 'other' || $to == 'all') && $outside) {
          $to = $node->get('field_to')->value;
          foreach ($custom_results as $key_test => $value) {
            // Distinct workaround, view is outputting duplicate nodes.
            if (isset($custom_results[$key_test]['nid'])) {
              if ($custom_results[$key_test]['nid'] == $nid) {
                $new_item = FALSE;
              }
            }
          }
          if ($new_item) {
            $custom_results[$id]['newstype'] = $node->get('field_newstype')->entity->getName();
            $custom_results[$id]['term_id'] = $node->get('field_newstype')->target_id;
            $custom_results[$id]['summary'] = $summary;
            $custom_results[$id]['from'] = $node->get('field_from')->value;
            $custom_results[$id]['nid'] = $node->id();
            $custom_results[$id]['href'] = $this->getUrlForNode($node, $lang);
            $custom_results[$id]['image'] = $this->convertBodyToImg($node, $narrow);
            $custom_results[$id]['image_narrow'] = 0;
            if ($narrow) {
              $custom_results[$id]['image_narrow'] = 1;
            }
            $custom_results[$id]['title'] = $node->getTitle();
            $custom_results[$id]['weight'] = $termweight;
          }
        }

      }
    }

    ksort($custom_results, SORT_NUMERIC);
    // AgriAdminHelper::addToLog('<pre>object ' . print_r($custom_results, TRUE) . ' </pre>', TRUE);.
    return $custom_results;
  }

  /**
   * Construct the absolute path to the node alias.
   *
   * @return string
   *   The absolute uri.
   */
  public function getUrlForNode($node, $lang = 'en') {
    $host_path = \Drupal::request()->getSchemeAndHttpHost();
    $base_path = \Drupal::request()->getBasePath();
    if (strlen($base_path) > 0) {
      if (stripos(strval($base_path), '/') < 0) {
        $host_path = $host_path . '/' . $base_path;
      }
      else {
        $host_path = $host_path . $base_path;
      }
    }
    $relative = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id(), $lang);
    if (isset($relative)) {
      $url = $host_path . '/' . $lang . $relative;
    }
    else {
      $url = $host_path . '/' . $lang . '/node/' . $node->id();
    }
    return $url;
  }

  /**
   * Get the image attributes based on the file_uri using a query and params by reference.
   *
   * @param string $file_uri
   *   Example public://image/example.jpg.
   * @param int $img_w
   *   The image width by reference.
   * @param int $img_h
   *   The image height by reference.
   * @param string $img_alt
   *   The image alt text by reference.
   */
  public function getImageAttributes($file_uri, &$img_w = 0, &$img_h = 0, &$img_alt = '') {
    /* Example result:
    stdClass Object
    (
    [uri] => public://2020-02/cue_feb_2020-eng.jpg
    [fid] => 5
    [filename] => cue_feb_2020-eng.jpg
    [image_width] => 876
    [image_height] => 271
    [image_alt] => January-February edition of CUE is now available
    )
     */
    // $file_uri = 'public://2020-02/cue_feb_2020-eng.jpg';
    // $database = \Drupal::database();
    // Use dependency injection database instead.
    $query = $this->database->select('file_managed', 'f');

    // Add extra detail to this query object: a condition, fields and a range.
    $query->innerJoin('media__image', 'mi', 'f.fid = mi.image_target_id');
    $query->fields('f', ['uri', 'fid', 'filename']);
    $query->fields('mi', ['image_width', 'image_height', 'image_alt']);
    $query->condition('f.uri', $file_uri, '=');
    $query->range(0, 1);
    $results = $query->execute();
    if (is_object($results) && count(get_object_vars($results)) > 0) {
      $result = $results->fetchObject();
      $img_w = $result->image_width;
      $img_h = $result->image_height;
      $img_alt = $result->image_alt;
      if (is_numeric($img_w) && is_numeric($img_h) && $img_w > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   *
   */
  public function convertBodyToImg($node, &$narrow = FALSE) {
    $host_path = \Drupal::request()->getSchemeAndHttpHost();
    $base_path = \Drupal::request()->getBasePath();
    // This grabs the rendered body so that we can get embedded images using media browser OR legacy img element.
    $render_array = $node->get('body')->view('full');
    $html_output = \Drupal::service('renderer')->renderRoot($render_array);
    $regex_img = '/<img.*\B \/>/m';

    preg_match_all($regex_img, $html_output, $matches, PREG_SET_ORDER, 0);
    if (isset($matches[0][0])) {
      $image_element = $matches[0][0];
      $image_element = $this->stripWidthAndHeight($image_element);
      // Now get the file resource link.
      // regex101.com.
      $regex_src = '/src="?\'?(.*\.[a-zA-Z][a-zA-Z][a-zA-Z])/m';
      preg_match_all($regex_src, $image_element, $match_src, PREG_SET_ORDER, 0);
      // Drupal uses public:// as a placeholder for path/to/sites/default/files.
      $public_thing = "public://";
      // The image style machine name.
      $image_style_name = 'courriel';
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($image_style_name);
      if (isset($match_src[0][1]) && !is_null($style)) {
        $src_path = $match_src[0][1];
        if (!isset($src_path)) {
          $src_path = '';
        }
        $pos_dot = strpos($src_path, '.');
        if (strlen($src_path) > ($pos_dot + 4)) {
          $src_path = substr($src_path, 0, $pos_dot + 4);
        }
        // Get the original image URI.
        if (stripos($src_path, $base_path) >= 0) {
          $file_uri = str_replace($base_path . '/sites/default/files/', $public_thing, $src_path);
        }
        else {
          $file_uri = str_replace('/sites/default/files/', $public_thing, $src_path);
        }
        $file_uri = urldecode($file_uri);
        $destination_nostyle = \Drupal::service('file_system')->realpath($file_uri);
        if (strlen($base_path) > 0 && !file_exists($file_uri)) {
          $file_uri = str_replace('/sites/default/files/', $public_thing, $src_path);
          $file_uri = urldecode($file_uri);
          $destination_nostyle = \Drupal::service('file_system')->realpath($file_uri);
        }
        if (file_exists($file_uri) || file_exists($destination_nostyle)) {
          // Get the image attributes like width/height.
          if (stripos($file_uri, 'styles') >= 0) {
            // Workaround if the image is already set to an image style, get the original.
            // regex101.com.
            $regex_style = '/^.*\/public\/(.*\.[a-zA-Z][a-zA-Z][a-zA-Z])/';
            preg_match($regex_style, $image_element, $match_style_src, PREG_OFFSET_CAPTURE, 0);
            if (isset($match_style_src[1][0])) {
              $file_uri = $public_thing . $match_style_src[1][0];
            }
          }
          $return_code = $this->getImageAttributes($file_uri, $img_w, $img_h, $img_alt);
          if ($return_code && ($img_h > $img_w || $img_w < 300 || ((float) $img_w) < ($img_h * 1.75))) {
            // Narrow image style.
            $narrow = TRUE;
            // The image style machine name.
            $image_style_name = 'courriel_narrow';
          }
          else {
            $narrow = FALSE;
            // The image style machine name.
            $image_style_name = 'courriel';
          }
          // Load the image style.
          $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($image_style_name);
          // Get the styled image derivative.
          $destination = $style->buildUri($file_uri);
          // If the derivative doesn't exist yet (as the image style may have been
          // added post launch), create it.
          if (!file_exists($destination) && !is_null($style)) {
            $style->createDerivative($file_uri, $destination);
          }
          $styled_file_uri = file_url_transform_relative($style->buildUrl($file_uri));
          $image_element = str_replace($src_path, $styled_file_uri, $image_element);
        }
      }
      if (strlen($base_path) > 0) {
        if (stripos(strval($base_path), '/') < 0) {
          $host_path = $host_path . '/' . $base_path;
        }
        else {
          $host_path = $host_path . $base_path;
        }
        $search_for = 'src="' . $base_path . '/sites/default/files';
        if ($narrow) {
          $replace_with = 'src="' . $host_path . '/sites/default/files';
        }
        else {
          $replace_with = 'style="margin-left:auto;margin-right:auto;" src="' . $host_path . '/sites/default/files';
        }
      }
      else {
        $search_for = 'src="/sites/default/files';
        if ($narrow) {
          $replace_with = 'src="' . $host_path . '/sites/default/files';
        }
        else {
          $replace_with = 'style="margin-left:auto;margin-right:auto;" src="' . $host_path . '/sites/default/files';
        }
      }
      // Images must be visible via email therefore must have absolute url.
      $image_element_absolute = str_replace($search_for, $replace_with, $image_element);
      if (stripos($image_element_absolute, 'alt=') <= 0) {
        // WCAG fix, img elements must have an alt attribute and it can be empty.
        $image_element_absolute = str_replace('src=', 'alt="" src=', $image_element_absolute);
      }
      return $image_element_absolute;
    }
    else {
      return NULL;
    }
  }

  /**
   *
   */
  private function stripWidthAndHeight($img_element, &$width = 0, &$height = 0) {
    // Used for number inside quotes.
    $regex_value = '/\"?\'?(\d{2,})[\"|\']/m';
    $this->tempWidth = 0;
    $this->tempHeight = 0;
    // regex101.com.
    $regex_width = '/(width="?\'?\d{2,}[\"|\'])/m';
    preg_match_all($regex_width, $img_element, $match_width, PREG_SET_ORDER, 0);
    if (isset($match_width[0][0]) && stripos($match_width[0][0], 'width') >= 0) {
      $width = $match_width[0][0];
      $img_element = str_replace($width, '', $img_element);
    }
    // regex101.com.
    $regex_height = '/(height="?\'?\d{2,}[\"|\'])/m';
    preg_match_all($regex_height, $img_element, $match_height, PREG_SET_ORDER, 0);
    if (isset($match_height[0][0]) && stripos($match_height[0][0], 'width') >= 0) {
      $height = $match_height[0][0];
      $img_element = str_replace($height, '', $img_element);
    }
    if (!is_numeric($width)) {
      $width = 0;
    }
    if (!is_numeric($height)) {
      $height = 0;
    }
    return $img_element;
  }

  /**
   *
   */
  private function sortNewsNodeArrayByArray(array $array, array $orderArray) {
    $ordered = [];
    if (empty($orderArray)) {
      // Default show all records in default order.
      return $array;
    }
    foreach ($orderArray as $key => $value) {
      foreach ($array as $innerKey => $innerValue) {
        if ($array[$innerKey]['nid'] == $value) {
          $ordered[$key] = $array[$innerKey];
          unset($array[$innerKey]);
        }
      }
    }
    return $ordered + $array;
  }

  /**
   *
   */
  public function reOrderNodeWeights($lang = 'en', $outside = FALSE) {
    $unorderedNodes = $this->getNewsItems($lang, $outside);
    // Safe default values.
    $reorderedNodes = $unorderedNodes;
    $orderedNodes = $this->getNewsNids($lang);
    if (!empty($orderedNodes)) {
      $reorderedNodes = $this->sortNewsNodeArrayByArray($unorderedNodes, $orderedNodes);
    }
    return $reorderedNodes;
  }

  /**
   *
   */
  private function sortNewsTypeArrayByArray(array $array, array $orderArray) {
    $ordered = [];
    foreach ($orderArray as $key => $value) {
      foreach ($array as $innerKey => $innerValue) {
        if ($array[$innerKey]['tid'] == $value) {
          $ordered[$key] = $array[$innerKey];
          unset($array[$innerKey]);
        }
      }
    }
    return $ordered + $array;
  }

  /**
   *
   */
  public function reOrderTypeWeights($lang = 'en', $outside = FALSE) {
    $unorderedTypes = $this->getTypesByWeight($lang, $outside);
    // Safe default values.
    $reorderedTypes = $unorderedTypes;
    // Return $unorderedTypes;.
    $orderedTypes = $this->getTypeWeights();
    if (!empty($orderedTypes)) {
      $reorderedTypes = $this->sortNewsTypeArrayByArray($unorderedTypes, $orderedTypes);
    }
    return $reorderedTypes;
  }

  /**
   *
   */
  public function getTypesByWeight($lang = 'en', $outside = FALSE) {
    $news_items = $this->getNewsItems($lang, $outside);
    // array.
    $types_by_weight = [];
    $vid = 'news_type';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term = Term::load($term->tid);
      $term = $term->getTranslation($lang);
      $word_array = str_word_count($term->getName(), 1);
      // Do not need other terms that have no records (for now).
      /*if (!isset($types_by_weight[$term->getWeight()]['has_items'])) {
      $types_by_weight[$term->getWeight()]['has_items'] = 0;
      }
      $types_by_weight[$term->getWeight()] = [
      'name' => $term->getName(),
      'tid' => $term->id(),
      'first_word' => strtolower($word_array[0])
      ];*/
      foreach ($news_items as $itemkey => $itemvalue) {
        if ($term->id() == $itemvalue['term_id']) {
          $types_by_weight[$term->getWeight()] = [
            'name' => $term->getName(),
            'tid' => $term->id(),
            'first_word' => strtolower($word_array[0]),
          ];
          $types_by_weight[$term->getWeight()]['has_items'] = 1;
        }
      }
    }

    return $types_by_weight;
  }

  /**
   *
   */
  public function getNewsTypes($lang = 'en') {
    // array.
    $news_types = [];
    $vid = 'news_type';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term = Term::load($term->tid);
      $term = $term->getTranslation($lang);
      $word_array = str_word_count($term->getName(), 1);
      $news_types[$term->id()] = [
        'name' => $term->getName(),
        'weight' => $term->weight,
        'first_word' => strtolower($word_array[0]),
      ];
    }
    return $news_types;
  }

  /**
   *
   */
  public function getSomething() {
    $keys = [];

    if (isset($_GET['type'])) {
      $type = $_GET['type'];
    }

    return new JsonResponse(array_merge(['status' => TRUE], $keys));
  }

}
