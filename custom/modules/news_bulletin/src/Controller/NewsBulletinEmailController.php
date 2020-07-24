<?php

namespace Drupal\news_bulletin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\views\Views;
use Drupal\agri_admin\AgriAdminHelper;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;


class NewsBulletinEmailController extends ControllerBase {

  protected $tempStore;

  // Pass the dependency to the object constructor
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    // For "news_bulletin," any unique namespace will do
    $this->tempStore = $temp_store_factory->get('news_bulletin');
  }

  // Uses Symfony's ContainerInterface to declare dependency to be passed to constructor
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }


  // Read some temporary data
  public function getTypeWeights() {
    $news_type_weights = $this->tempStore->get('news_type_weights');
    if (!isset($news_type_weights)) {
      $news_type_weights = array();
    }
    return $news_type_weights;
    // Do other stuff, return a render array, etc...
  }


  // Read some temporary data
  public function getNewsNids() {
    $news_nids_selected = $this->tempStore->get('news_nids');
    if (!isset($news_nids_selected)) {
      $news_nids_selected = array();
    }
    return $news_nids_selected;
  }

  /**
   * Display the markup.
   *
   * @return array
   */
  public function content() {
    return [
//      '#type' => 'markup',
      '#theme' => 'news_bulletin_email',
      '#news_types' => $this->getNewsTypes(),
      '#news_items' => $this->getNewsItems(),
      '#types_by_weight' => $this->reOrderTypeWeights(),
      '#news_types_fr' => $this->getNewsTypes('fr'),
      '#news_items_fr' => $this->getNewsItems('fr'),
      '#types_by_weight_fr' => $this->reOrderTypeWeights('fr')
//      '#markup' => $this->t('Hello, World!'),
//      '#attached' => ['library' => ['email_template/bulletins']] // OR add this through the twig template
    ];
  }


  public function getNewsItems($lang = 'en') {

    $view = Views::getView('newsatworkbulletin');

    $view->setDisplay('rest_export_1');
    $view->preExecute();
    $view->execute();

    // $myresults = $view->preview();  = array
    // $myresults = $view->render(); // = array
    //$myresults = $view->result; // = array
    $custom_results = [];
    foreach ($view->result as $id => $result) {
      $node = $result->_entity;
      $nid = $node->id();
      if (!in_array($nid, $this->getNewsNids())) {
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
      $max = 110;
      if ($summary_length >= $max) {
        $max = strpos($summary, ' ', $max);
        $summary = substr($summary, 0, $max) . ' ...';
      }
      $new_item = true;
      foreach ($custom_results as $key_test => $value) {
        // Distinct workaround, view is outputting duplicate nodes.
        if (isset($custom_results[$key_test]['nid'])) {
          if ($custom_results[$key_test]['nid'] == $nid) {
            $new_item = false;
          }
        }
      }
      if ($new_item) {
        $custom_results[$id]['newstype'] = $node->get('field_newstype')->entity->getName();
        $custom_results[$id]['term_id'] = $node->get('field_newstype')->target_id;
        $custom_results[$id]['summary'] = $summary;
        $custom_results[$id]['from'] = $node->get('field_from')->value;
        $custom_results[$id]['nid'] = $node->id();
        $custom_results[$id]['title'] = $node->getTitle();
        $custom_results[$id]['weight'] = $termweight;
      }
    }

    ksort($custom_results, SORT_NUMERIC);
    //AgriAdminHelper::addToLog('<pre>object ' . print_r($custom_results, TRUE) . ' </pre>', TRUE);
    return $custom_results;
  }

  private function sortArrayByArray(array $array, array $orderArray) {
      $ordered = array();
      foreach ($orderArray as $key) {
          if (array_key_exists($key, $array)) {
              $ordered[$key] = $array[$key];
              unset($array[$key]);
          }
      }
      return $ordered + $array;
  }

  public function reOrderTypeWeights($lang = 'en') {
    $unorderedTypes = $this->getTypesByWeight($lang);
    $reorderedTypes = $unorderedTypes; // Safe default values.
    //return $unorderedTypes;

    $orderedTypes = $this->getTypeWeights();
    if (!empty($orderedTypes)) {
      $reorderedTypes = $this->sortArrayByArray($unorderedTypes, $orderedTypes);
    }
    return $reorderedTypes;
  }

  public function getTypesByWeight($lang = 'en') {
    $news_items = $this->getNewsItems();
    $types_by_weight = []; // array.
    $vid = 'news_type';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term = \Drupal\taxonomy\Entity\Term::load($term->tid);
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
            'first_word' => strtolower($word_array[0])
          ];
          $types_by_weight[$term->getWeight()]['has_items'] = 1;
        }
      }
    }

    return $types_by_weight;
  }

  public function getNewsTypes($lang = 'en') {
    $news_types = []; // array.
    $vid = 'news_type';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $term = \Drupal\taxonomy\Entity\Term::load($term->tid);
      $term = $term->getTranslation($lang);
      $word_array = str_word_count($term->getName(), 1);
      $news_types[$term->id()] = [
        'name' => $term->getName(),
        'weight' => $term->weight,
        'first_word' => strtolower($word_array[0])
      ];
    }
    return $news_types;
  }

  public function getSomething() {
    $keys = array();

    if (isset($_GET['type'])) {
      $type = $_GET['type'];
    }

    return new JsonResponse(array_merge(array('status' => true), $keys));
  }

}
