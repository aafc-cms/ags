<?php

   $query = \Drupal::entityQuery('node');
   $group = $query->orConditionGroup()
      ->accessCheck(FALSE)
      ->condition('type', 'page', '=')
      ->condition('type', 'landing_page', '=')
      ->condition('type', 'empl', '=')
      ->condition('type', 'news', '=');
   $query->condition($group)
     ->accessCheck(FALSE);
   $nids = $query->accessCheck(FALSE)
     ->execute();
   $path_alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
   foreach ($nids as $nid) {
      // langcode = en & langcode = fr
      $actual_alias_e = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/node/' . $nid, 'en');
      $actual_alias_f = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/node/' . $nid, 'fr');
  
      // Load all path alias for this node.
      $alias_objects_e = $path_alias_storage->loadByProperties([
        'path' => '/node/' . $nid, 'langcode' => 'en',
      ]);
      if (count($alias_objects_e) > 1) {
         //keep the latest revision
         array_pop($alias_objects_e);
         foreach ($alias_objects_e as $alias_object_e) {
            // delete duplicate record only
            if ($alias_object_e->get('alias')->value == $actual_alias_e) {
               $alias_object_e->delete();
            }
         }
      }

      // langcode = French
      // Load all path alias for this node.
      $alias_objects_f = $path_alias_storage->loadByProperties([
         'path' => '/node/' . $nid, 'langcode' => 'fr',
      ]);
      if (count($alias_objects_f) > 1) {
         //keep the latest revision
         array_pop($alias_objects_f);
         foreach ($alias_objects_f as $alias_object_f) {
            // delete duplicate record only
            if ($alias_object_f->get('alias')->value == $actual_alias_f) {
               $alias_object_f->delete();
            }
         }
      }
   }
