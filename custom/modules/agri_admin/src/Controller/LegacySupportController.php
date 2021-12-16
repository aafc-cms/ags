<?php

namespace Drupal\agri_admin\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Agri Admin routes.
 */
class LegacySupportController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   *
   */
  public function getDcridFromNid($nid) {
    // Retrieves a PDOStatement object
    // http://php.net/manual/en/pdo.prepare.php
    $sth = $this->connection->select('node', 'n')
      ->fields('n', ['dcr_id'])
      ->condition('n.nid', $nid, '=');

    // Execute the statement.
    $data = $sth->execute();

    // Get only one result.
    $result = $data->fetch();
    if (!empty($result) && property_exists($result, 'dcr_id') && isset($result->dcr_id)) {
      $value = $result->dcr_id;
      return $value;
    }
    else {
      return NULL;
    }
  }

  /**
   *
   */
  public function getNidFromDcrId($dcrid) {
    // Retrieves a PDOStatement object
    // http://php.net/manual/en/pdo.prepare.php
    $sth = $this->connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->condition('n.dcr_id', $dcrid, '=');

    // Execute the statement.
    $data = $sth->execute();

    // Get only one result.
    $result = $data->fetch();
    if (property_exists($result, 'nid') && isset($result->nid)) {
      $value = $result->nid;
      return $value;
    }
    else {
      return NULL;
    }
  }

  /**
   * Builds the response.
   */
  public function build() {
    $id = $this->getIdFromGetParam();
    if (empty($id)) {
      // Get out early.
      return new JsonResponse(['status' => TRUE, 'message' => ['']]);
    }
    // Retrieves a \Drupal\Core\Database\Connection which is a PDO instance.
    $dcr_id_column_exists = $this->connection->schema()->fieldExists('node', 'dcr_id');
    if (!$dcr_id_column_exists) {
      // Get out early.
      return new JsonResponse(['status' => TRUE, 'message' => ['']]);
    }

    $nid = NULL;
    $dcr_id = NULL;
    if (strlen($id) < 13) {
      $id = $this->getDcridFromNid($id);
    }
    elseif (strlen($id) == 13) {
      $id = $this->getNidFromDcrId($id);
    }

    if (empty($id)) {
      return new JsonResponse(['status' => TRUE, 'message' => ['']]);
    }
    else {
      return new JsonResponse(['status' => TRUE, 'message' => [$id]]);
    }
  }

  /**
   * Get the nids from the _GET param (validate it).
   */
  private function getIdFromGetParam() {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Handle array() parameter like [].
    $nids = Xss::filter(\Drupal::request()->query->get('nids'));
    if (!is_array($nids) || (!isset($nids) || empty($nids))) {
      $nids = [];
    }

    // Handle comma seperated param and turn it into an array.
    if (empty($nids) && isset($_GET['nids'])) {
      foreach (explode(',', $_GET['nids']) as $nid) {
        if ($nid == (int) $nid) {
          $nids[] = $nid;
        }
      }
    }
    if (!empty($nids)) {
      foreach ($nids as $nid) {
        if (!is_numeric($nid)) {
          return NULL;
        }
      }
    }

    if (empty($nids)) {
      return NULL;
    }
    // Just return first one.
    return reset($nids);
  }

}
