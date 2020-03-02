<?php

namespace Drupal\agri_admin;

//use Drupal\something\AgriUtils;


class AgriAdminHelper {

  static public function addMessage($message) {
    \Drupal::messenger()->addMessage($message);
  }

  static public function addToLog($message) {
    \Drupal::logger('agri_admin')->notice($message);
  }

  static public function postUpdateProcess($action, $entity_id) {
    static::addToLog($action . ' entity_id ' . $entity_id);
  }

}
