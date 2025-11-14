<?php

namespace Drupal\agrisource_search_processors\EventSubscriber;

use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_solr\Event\PostExtractResultsEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pins the Human resources overview node to the top for specific queries.
 */
final class PinItemSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::POST_EXTRACT_RESULTS => 'onPostExtractResults',
    ];
  }

  /**
   * Move node 62 to the top for "Human resources" / "Ressources humaines".
   */
  public function onPostExtractResults(PostExtractResultsEvent $event): void {
    $result_set = $event->getSearchApiResultSet();
    $query = $result_set->getQuery();

    // Only for fully processed queries (normal Search page).
    if ($query->getProcessingLevel() !== QueryInterface::PROCESSING_FULL) {
      return;
    }

    // Get the original search keys.
    $keys = $query->getOriginalKeys();
    if (empty($keys)) {
      return;
    }
    if (is_array($keys)) {
      $keys = implode(' ', $keys);
    }
    $keys = mb_strtolower(trim($keys));

    // Only pin for these exact queries.
    if (!in_array($keys, ['human resources', 'ressources humaines'], TRUE)) {
      return;
    }

    // Reorder results so node 62 is first (for both en/fr if present).
    $result_items = $result_set->getResultItems();

    $pin_ids = [
      'entity:node/62:en',
      'entity:node/62:fr',
    ];

    $pinned = [];
    foreach ($pin_ids as $id) {
      if (isset($result_items[$id])) {
        $pinned[$id] = $result_items[$id];
        unset($result_items[$id]);
      }
    }

    // Nothing to pin.
    if (!$pinned) {
      return;
    }

    // Put pinned items first, keep original order for the rest.
    $result_set->setResultItems($pinned + $result_items);
  }

}

