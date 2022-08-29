<?php

  $connection = \Drupal\Core\Database\Database::getConnection();
  $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $lang = 'en';
  $num_updated = $connection->update('paragraph_revision__field_class_id')
    ->fields(['field_class_id_value' => 'IT'])
    ->condition('field_class_id_value', 'CS', '=')
    ->execute();
  // Execute the statement.

  echo "*****************************************************";
  echo 'Revisions updated to new class id:' . $num_updated . "\n";
  echo "*****************************************************";

  $num_updated = $connection->update('paragraph__field_class_id')
    ->fields(['field_class_id_value' => 'IT'])
    ->condition('field_class_id_value', 'CS', '=')
    ->execute();
  // Execute the statement.

  echo "*****************************************************";
  echo 'Entities updated to new class id:' . $num_updated . "\n";
  echo "*****************************************************";

