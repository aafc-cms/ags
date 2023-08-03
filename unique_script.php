<?php

/**
 *  Return array of fid.
 **/
function getDuplicateFids() {
  $database = \Drupal::database();
  $result = $database->query('select fid from filehash group by sha256 having count(sha256) > 1')
    ->fetchAllAssoc('fid', \PDO::FETCH_ASSOC);
  $fids = array_keys($result);
  return $fids;
}

$fids = getDuplicateFids();

foreach ($fids as $fid) {
  $file = \Drupal\file\Entity\File::load($fid);
  echo "Duplicate file: " . $file->getFileUri();
  echo "\n";
}


echo count($fids) . ' duplicates in total';
