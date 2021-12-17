<?php

namespace Drupal\news_bulletin\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\views\Views;
use Drupal\core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * For the news bulletin email, master controller logic.
 */
class NewsBulletinController extends ControllerBase {

  /**
   * The Tempstore of news nids.
   *
   * @var Drupal\core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Pass the dependency to the object constructor.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    // For "news_bulletin," any unique namespace will do.
    $this->tempStore = $temp_store_factory->get('news_bulletin');
  }

  /**
   * Uses Symfony's ContainerInterface to declare dependency to be passed to constructor.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * Set the temp config values used by ajax call.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The status of the ajax request.
   */
  public function setTempConfig() {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // @todo Look this up
    $langseg = [
      'en' => 'en',
      'fr' => 'fr',
    ];

    // Handle array() parameter like [].
    $news_type_weights = Xss::filter(\Drupal::request()->query->get('tids'));
    if (!is_array($news_type_weights) || (!isset($news_type_weights) || empty($news_type_weights))) {
      $news_type_weights = [];
    }

    // Handle comma seperated param and turn it into an array.
    if (empty($news_type_weights) && isset($_GET['tids'])) {
      foreach (explode(',', $_GET['tids']) as $tid) {
        if ($tid == (int) $tid) {
          $news_type_weights[] = $tid;
        }
      }
    }

    // Handle array() parameter like [].
    $news_nids = Xss::filter(\Drupal::request()->query->get('nids'));
    if (!is_array($news_nids) || (!isset($news_nids) || empty($news_nids))) {
      $news_nids = [];
    }

    // Handle comma seperated param and turn it into an array.
    if (empty($news_nids) && isset($_GET['nids'])) {
      foreach (explode(',', $_GET['nids']) as $nid) {
        if ($nid == (int) $nid) {
          $news_nids[] = $nid;
        }
      }
    }

    if (empty($news_type_weights) && empty($news_nids)) {
      return new JsonResponse(['status' => TRUE, 'message' => ['no changes']]);
    }
    if (!empty($news_type_weights) && !empty($news_nids)) {
      $this->setTypeWeights($news_type_weights);
      $this->setNewsNids($news_nids);
      return new JsonResponse([
        'status' => TRUE
        , 'message' => ['node selection and term weights updated'],
      ]
      );
    }
    if (!empty($news_type_weights)) {
      $this->setTypeWeights($news_type_weights);
      $this->setNewsNids([]);
      $tempValuesUpdated = $this->getTempValues();
      return new JsonResponse([
        'status' => TRUE,
        'message' => ['term weights updated '],
      ]
      );
    }
    if (!empty($news_nids)) {
      $this->setNewsNids($news_nids);
      return new JsonResponse([
        'status' => TRUE,
        'message' => ['news nids selection updated '],
      ]
      );
    }
    return new JsonResponse([
      'status' => FALSE,
      'message' => ['This should not occur'],
    ]
    );
  }

  /**
   * Get the temporary config used by ajax (js).
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The temporary values (nids).
   */
  public function getTempConfig() {
    return new JsonResponse([
      'status' => TRUE,
      'message' => ['This is the temp config'],
      'data' => [$this->getTempValues()],
    ]
    );
  }

  /**
   * Save some temporary data (terms selected and their order/weight (is important)).
   */
  public function setTypeWeights($terms_and_order_or_weights = []) {
    $this->tempStore->set('news_type_weights', $terms_and_order_or_weights);
  }

  /**
   * Save some temporary data (nids selected).
   */
  public function setNewsNids($news_nids_selected = []) {
    $this->tempStore->set('news_nids', $news_nids_selected);
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
  public function getTempValues() {
    $news_type_weights = $this->tempStore->get('news_type_weights');
    if (!isset($news_type_weights)) {
      $news_type_weights = [];
    }
    $news_nids_selected = $this->tempStore->get('news_nids');
    if (!isset($news_nids_selected)) {
      $news_nids_selected = [];
    }
    $config_array = [
      'tids' => $news_type_weights,
      'nids' => $news_nids_selected,
    ];
    return $config_array;
  }

  /**
   * Display the markup.
   *
   * @return array
   *   The theming information used for the template.
   */
  public function content() {
    if (\Drupal::routeMatch()->getRouteName() == 'news_bulletin.content') {
      if (\Drupal::currentUser()->isAuthenticated()) {
        // Was doing this to eliminate admin rendering but template changes should have stripped most of that out.
        // Uncomment the two lines below if we want to force a logout.
        // $session_manager = \Drupal::service('session_manager');
        // $session_manager->delete(\Drupal::currentUser()->id());
      }
    }

    if (\Drupal::routeMatch()->getRouteName() == 'news_bulletin.content') {
      return [
        '#theme' => 'news_bulletin',
        '#news_types' => $this->getNewsTypes(),
        '#news_items' => $this->getNewsItems(FALSE),
        '#types_by_weight' => $this->getTypesByWeight(FALSE),
      ];
    }
    else {
      if (\Drupal::routeMatch()->getRouteName() == 'news_bulletin_nonncr.content') {
        return [
          '#theme' => 'news_bulletin',
          '#news_types' => $this->getNewsTypes(),
          '#news_items' => $this->getNewsItems(TRUE),
          '#types_by_weight' => $this->getTypesByWeight(TRUE),
        ];
      }
    }
  }

  /**
   * Retrieve the sorted news items.
   *
   * @param bool $outside
   *   Whether this is for outside or inside.
   *
   * @return array
   *   The news items sorted with array keys for the values.
   */
  public function getNewsItems($outside = FALSE) {

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
      $newstype_id = $node->get('field_newstype')->target_id;
      $term = $node->get('field_newstype')->entity;
      $termweight = $term->getWeight();
      $summary = $node->get('body')->summary;
      $summary_length = strlen($summary);
      $max = 110;
      if ($summary_length >= $max) {
        $max = strpos($summary, ' ', $max);
        $summary = substr($summary, 0, $max) . ' ...';
      }
      $nid = $node->id();
      $new_item = TRUE;
      $to = $node->get('field_to')->value;
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
          $type_name = $node->get('field_newstype')->entity->getName();
          $word_array = str_word_count($type_name, 1);
          $custom_results[$id]['first_word'] = strtolower($word_array[0]);
          $custom_results[$id]['newstype'] = $type_name;
          $custom_results[$id]['term_id'] = $node->get('field_newstype')->target_id;
          $custom_results[$id]['summary'] = $summary;
          $custom_results[$id]['from'] = $node->get('field_from')->value;
          $custom_results[$id]['nid'] = $node->id();
          $custom_results[$id]['title'] = $node->getTitle();
          $custom_results[$id]['weight'] = $termweight;
        }
      }
      else {
        if (($to == 'other' || $to == 'all') && $outside) {
          foreach ($custom_results as $key_test => $value) {
            // Distinct workaround, view is outputting duplicate nodes.
            if (isset($custom_results[$key_test]['nid'])) {
              if ($custom_results[$key_test]['nid'] == $nid) {
                $new_item = FALSE;
              }
            }
          }
          if ($new_item) {
            $type_name = $node->get('field_newstype')->entity->getName();
            $word_array = str_word_count($type_name, 1);
            $custom_results[$id]['first_word'] = strtolower($word_array[0]);
            $custom_results[$id]['newstype'] = $type_name;
            $custom_results[$id]['term_id'] = $node->get('field_newstype')->target_id;
            $custom_results[$id]['summary'] = $summary;
            $custom_results[$id]['from'] = $node->get('field_from')->value;
            $custom_results[$id]['nid'] = $node->id();
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
   * Retrieve the sorted news types by weight.
   *
   * @param bool $outside
   *   Whether this is for outside or inside.
   *
   * @return array
   *   The news types sorted with array keys for the values.
   */
  public function getTypesByWeight($outside = FALSE) {
    $news_items = $this->getNewsItems($outside);
    // array.
    $types_by_weight = [];
    $vid = 'news_type';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      if (!isset($types_by_weight[$term->weight]['has_items'])) {
        $types_by_weight[$term->weight]['has_items'] = 0;
      }
      $word_array = str_word_count($term->name, 1);
      $types_by_weight[$term->weight] = [
        'nameshort' => substr($term->name, 0, 25),
        'name' => $term->name,
        'tid' => $term->tid,
        'first_word' => strtolower($word_array[0]),
      ];
      foreach ($news_items as $itemkey => $itemvalue) {
        if ($term->tid == $itemvalue['term_id']) {
          $types_by_weight[$term->weight]['has_items'] = 1;
        }
      }
    }

    return $types_by_weight;
  }

  /**
   * Retrieve the news types.
   *
   * @return array
   *   The news types with array keys for the values.
   */
  public function getNewsTypes() {
    // array.
    $news_types = [];
    $vid = 'news_type';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $word_array = str_word_count($term->name, 1);
      $news_types[$term->tid] = [
        'name' => $term->name,
        'weight' => $term->weight,
        'first_word' => strtolower($word_array[0]),
      ];
    }
    return $news_types;
  }

}
