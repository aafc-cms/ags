<?php

namespace Drupal\agri_admin\Plugin\Linkit\Matcher;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\NodeMatcher;

/**
 * Provides a Linkit matcher that excludes archived nodes.
 *
 * @Matcher(
 *   id = "filtered_node_matcher",
 *   label = @Translation("Filtered Node Matcher"),
 *   description = @Translation("A Linkit matcher that excludes archived nodes."),
 *   target_entity = "node",
 *   provider = "agri_admin",
 *   weight = 10
 * )
 */
class FilteredNodeMatcher extends NodeMatcher {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($search_string) {
    $query = parent::buildEntityQuery($search_string);

    // Add a query tag so we can alter it later.
    $query->addTag('linkit_filter_moderation_state');

    return $query;
  }
}
