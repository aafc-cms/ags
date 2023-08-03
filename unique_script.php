<?php

/**
 *  Return sha256 for fid.
 **/
function getHashByFid($fid) {
  $database = \Drupal::database();
  $result = $database->query('select sha256 from filehash where fid = :fid', [ ':fid' => $fid ])
    ->fetchAllAssoc('sha256', \PDO::FETCH_ASSOC);
  $sha256s = array_keys($result);
  $sha256 = reset($sha256s);
  return $sha256;
}

/**
 *  Return all fids for sha256 value.
 **/
function getFidsBySha256($sha256) {
  $database = \Drupal::database();
  $result = $database->query('select fid from filehash where sha256 = :sha256', [ ':sha256' => $sha256 ])
    ->fetchAllAssoc('fid', \PDO::FETCH_ASSOC);
  $fids = array_keys($result);
  return $fids;
}

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
  $sha256 = getHashByFid($fid);
  $fids_with_matches = getFidsBySha256($sha256); 
  echo "****** sha256 = $sha256 *****";
  echo "\n";
  foreach ($fids_with_matches as $fid_matched) {
    echo "        fid = $fid_matched";
    echo "\n";
  }
  foreach ($fids_with_matches as $fid_matched) {
    $file = \Drupal\file\Entity\File::load($fid_matched);
    echo "Is duplicate= " . $file->getFileUri();
    echo "\n";
  }
  echo "*****************************";
  echo "\n";
}


echo count($fids) . ' duplicates in total';
