<?php

namespace Drupal\agri_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\agri_admin\AgriAdminHelper;
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
   * Builds the response.
   */
  public function build() {
    $nid = $this->getIdFromGetParam();
    // Retrieves a \Drupal\Core\Database\Connection which is a PDO instance
    $dcr_id_column_exists = $this->connection->schema()->fieldExists('node', 'dcr_id');
    if (!$dcr_id_column_exists) {
      return;
    }
    // Retrieves a PDOStatement object
    // http://php.net/manual/en/pdo.prepare.php
    $sth = $this->connection->select('node', 'n')
      ->fields('n', ['dcr_id'])
      ->condition('n.nid', $nid, '=');

    // Execute the statement
    $data = $sth->execute();

    // Get only one result.
    $result = $data->fetch();
    $value = $result->dcr_id;
    if (property_exists($result, 'dcr_id') && isset($result->dcr_id)) {
      return new JsonResponse(['status' => TRUE, 'message' => [$result->dcr_id]]);
    }
    if (empty($dcr_id)) {
      return new JsonResponse(['status' => TRUE, 'message' => ['']]);
    }
  }


  /**
   * Get the nids from the _GET param (validate it).
   */
  private function getIdFromGetParam() {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Handle array() parameter like [].
    $nids = \Drupal\Component\Utility\Xss::filter(\Drupal::request()->query->get('nids'));
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
      foreach($nids as $nid) {
        if (!is_numeric($nid)) {
          return NULL;
        }
      }
    }

    if (empty($nids)) {
      return NULL;
    }
    return reset($nids); // Just return first one.
  }

}
