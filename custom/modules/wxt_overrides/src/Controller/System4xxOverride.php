<?php

namespace Drupal\wxt_overrides\Controller;

use Drupal\agri_admin\Utils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for default HTTP 4xx responses.
 */
class System4xxOverride extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The block content entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * The block view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $blockViewBuilder;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $block_view_builder
   *   The block view builder.
   */
  public function __construct(EntityStorageInterface $storage, EntityViewBuilderInterface $block_view_builder) {
    $this->blockContentStorage = $storage;
    $this->blockViewBuilder = $block_view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('block_content'),
      $container->get('entity.manager')->getViewBuilder('block_content')
    );
  }

  public function getNidFromDcrId($dcrid) {
    $this->connection = \Drupal\Core\Database\Database::getConnection();
    // Retrieves a PDOStatement object
    // http://php.net/manual/en/pdo.prepare.php
    $sth = $this->connection->select('node', 'n')
      ->fields('n', ['nid'])
      ->condition('n.dcr_id', $dcrid, '=');

    // Execute the statement
    $data = $sth->execute();

    // Get only one result.
    $result = $data->fetch();
    if (!empty($result) && property_exists($result, 'nid') && isset($result->nid)) {
      $value = $result->nid;
      return $value;
    }
    else {
      return NULL;
    }
  }

  public function specialNodeFromDcrid($dcrid) {
    $dcrid=$dcrid.'';
    switch ($dcrid) {
      case '1287261736402':
        // Legacy Home Page dcrid
        Utils::gotoLegacy('<front>', [], '302'); // The new page.
        break;
      case '1311865754938':
        // Legacy Newsatwork View dcrid (is a view in Drupal).
        Utils::gotoLegacy('view.view_news_work.page_1', [], '302'); // The new page.
        break;
      case '1309887212500':
        // Legacy EO dcrid (is a view in Drupal).
        Utils::gotoLegacy('view.view_employmentopportunities.page_1', [], '302'); // The new page.
        break;
      case '1305895655987':
        // Legacy News submission form dcrid
        Utils::gotoLegacy('/node/49', [], '302'); // The new page.
        break;
      case '1307986207645':
        // Legacy EO submission form dcrid
        Utils::gotoLegacy('/node/50', [], '302'); // The new page.
        break;
      case '1311865754938':
        // Legacy Public Service Request form dcrid
        Utils::gotoLegacy('<front>', [], '302'); // @TODO , find the route for this.
        break;
      default:
        break;
    }
  }


  /**
   * The default 404 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on404() {
    $dcrid = \Drupal\Component\Utility\Xss::filter(\Drupal::request()->query->get('id'));
    if (strlen($dcrid) == 13 && is_numeric($dcrid)) {
      $this->specialNodeFromDcrid($dcrid);
      $nid = $this->getNidFromDcrId($dcrid);
      if ($nid) {
        Utils::gotoLegacy('/node/' . $nid, [], '302');
      }
    }

    // 404 Fallback message.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $homelink = '/'.$langcode;

    $response = '
    <div class="box">
      <div class="row">
        <div class="col-xs-3 col-sm-2 col-md-2 text-center mrgn-tp-md customalertimagesize">
          <span class="glyphicon glyphicon-warning-sign glyphicon-error customalertimagesize"></span>
        </div>
        <div class="col-xs-9 col-sm-10 col-md-10">
          <h2 class="mrgn-tp-md customalertheaderfont">' . $this->t("We couldn't find that Web page") . '</h2>
          <p class="pagetag"><strong>' . $this->t('Error 404') . '</strong></p>
        </div>
      </div>
      <p class="mrgn-tp-md customalertmsgfont">' . $this->t("We're sorry you ended up here. Sometimes a page gets moved or deleted, but hopefully we can help you find what you're looking for. What next?") . '</p>
      <p class="mrgn-tp-md customalertmsgfont">' . $this->t("Return to the ") . '<a href=' .$homelink .'>' . $this->t("home page"). '</a>.</p>
    </div>';

    // Lookup our custom 404 content block.
    $block_id = $this->blockContentStorage->loadByProperties([
      'info' => '404',
      'type' => 'basic',
    ]);
    if (!empty($block_id)) {
      $response = $this->blockViewBuilder->view(reset($block_id));
    }

    $block_array = [
      '#type' => 'container',
      '#markup' => render($response),
      '#attributes' => [
        'class' => '404 error',
      ],
      '#weight' => 0,
    ];
    $block_array['#attached']['library'][] = 'wxt_overrides/error-404';
    return $block_array;
  }

}
