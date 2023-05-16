<?php
// As requested, It only needs Landing page and Internal page.
$query  = \Drupal::entityQuery('node');
$group = $query 
  ->accessCheck(FALSE)
  ->orConditionGroup()
  ->condition('type', 'page', '=')      
  ->condition('type', 'landing_page ', '=');     
$nids = $query->condition($group)
  ->sort('type', 'ASC')
  ->sort('nid', 'DESC')
  ->accessCheck(TRUE)
  ->execute();

$entities = \Drupal\node\Entity\Node::loadMultiple($nids);

$file = 'None_Automatic_URL_Alias_Generated_Report.txt';
if (file_exists($file)) {
  unlink($file);
}
$headrecord = 'Node ID,' . 'Node Type,' . 'Moderation State,' . 'Page Title,'.'Page URL Alias' . PHP_EOL;
file_put_contents($file, $headrecord); 
foreach ($entities as $entity) {
  $eid = $entity->id();
  //$language_Id = $entity->language()->getId();
  //As requested, I provide English URL alias only in this version.
  $etype = $entity->bundle();
  $state = $entity->get('moderation_state')->getString();
  $title = $entity->getTitle();
  $url = $entity->toUrl()->toString();
  if ($etype == 'page') {
    $etype = 'Internal Page';
  }
  if ($etype == 'landing_page') {
    $etype = 'Landing Page';
  }
  if ($entity->path->pathauto == 0) {
    $record = $eid . ',' . $etype  . ',' . $state  . ',' . $title . ',' . $url . PHP_EOL;
    file_put_contents($file, $record, FILE_APPEND); 
  }
}
