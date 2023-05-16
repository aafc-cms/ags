<?php

drush_print('test');
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$query = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('nid', 855);
/*$query = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('status', 1),
  ->condition('type', 'page'),
  ->condition('body', 'data-entity-uuid'=57974d53-8d12-4930-918c-04a35811bf38', 'CONTAINS');*/
$nids = $query->execute();
$test = implode($nids, ',');
//drush_print($test);
//drush_print(print_r($nids, TRUE));
//drush_print(print_r(array_keys($render_array), TRUE));
$nodes = $node_storage->load(855);//$nids);
 // <h3 class="h5"><a data-entity-substitution="canonical" data-entity-type="node" data-entity-uuid=57974d53-8d12-4930-918c-04a35811bf38 href="/node/444">Employment opportunities</a></h3>

//$node->body->view('full');

$render_array = $nodes->get('body')->view('full');
$html_output = \Drupal::service('renderer')->renderRoot($render_array);
$regex2 = '/<img.*\B\/>/m';


preg_match_all($regex2, $html_output, $matches, PREG_SET_ORDER, 0);

// Print the entire match result
//echo "\n\n";
//echo $matches[0][0];
//echo "\n\n";
foreach($matches as $match) {
  echo "\n\n";
  echo $match[0];
  echo "\n\n";
}

 // <h3 class="h5"><a data-entity-substitution="canonical" data-entity-type="node" data-entity-uuid=57974d53-8d12-4930-918c-04a35811bf38 href="/node/444">Employment opportunities</a></h3>

//$node->body->view('full');
