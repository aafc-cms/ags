<?php

namespace Drupal\news_bulletin;

use Drupal\node\Entity\Node;
use Drupal\Core\Database\Connection;

class NewsBulletinHelper
{
  protected static $currentNid = null;
  protected static $titleEn = "";
  protected static $titleFr = "";
  protected static $urlAlias = "";

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Create a news node
   */
  static public function createNode($news=array(), $unixtimecreated=0, $uid=1) {
    $module_path = drupal_get_path('module', 'news_bulletin');

    // In order for the node to be published, must set the moderation state.
    // See example code: https://cgit.drupalcode.org/drupal/tree/core/modules/content_moderation/tests/src/Kernel/ModerationStateFieldItemListTest.php?h=8.6.x#n314
    $news = Node::create([
      'type' => 'news',
      'langcode' => 'en',
      'title' => $news['title_en'],
      'created' => $unixtimecreated,
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid,
      'status' => 1,
      'moderation_state' => 'published',
      'content_translation_source' => 'en',
/***
      'body' => [
        'summary' => '',
        'value' => '',
        'format' => 'full_html',
      ],
***/
    ]);
    $news->set('field_youtube_id', $news['id_en']);
    $news->set('field_date_released', $unixtimecreated);
    $news->setPublished(TRUE); // Probably not needed.
    $news->save();

    // In order for the node to be published, must set the moderation state.
    // See example code: https://cgit.drupalcode.org/drupal/tree/core/modules/content_moderation/tests/src/Kernel/ModerationStateFieldItemListTest.php?h=8.6.x#n314
    $news_fr = $news->addTranslation('fr', [
     'title' => $news['title_fr'],
     'moderation_state' => 'published',
     'content_translation_source' => 'en',
     'status' => 1,
    ]);
    $news_fr->setPublished(TRUE); // Probably not needed.
    $news_fr->save();
    $news_fr = $news_fr->getTranslation('fr');
    $news_fr->set('field_youtube_id', $news['id_fr']);
    $news_fr->set('field_date_released', $unixtimecreated);
    $news_fr->save();

    // Alternatively :
    // $news_fr = $news->addTranslation('fr');
    // $news_fr->title = $vid['title_fr'];
    // $news_fr->save();

        $DEBUG = FALSE;
        if ($DEBUG) {
          // Do something based on exception.
          if ($fp = fopen('saythistry.txt', 'a')) {
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, 'news->id(): ' . print_r($news->id(), TRUE));
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, 'get_class_methods: ' . print_r(get_class_methods($news), TRUE));
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, 'get object vars: ' .print_r(get_object_vars($news), TRUE));
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fwrite($fp, "\r\n");
            fclose($fp);
          }
        }
    if (isset($bulletin['title_en'])) {
      self::createUrlAlias('/node/' . $news->id(), $bulletin['title_en'], 'en');
    }
    if (isset($bulletin['title_fr'])) {
      self::createUrlAlias('/node/' . $news->id(), $bulletin['title_fr'], 'fr');
    }

  }


  static public function createUrlAlias($source, $alias, $language) {
    $alias = str_replace(' ', '-', $alias );//replace spaces with dashes
    $alias = strtolower($alias);
    $search = array('/é/', '/ç/', '/à/', '/ë/', '/ö/', '/ò/', '/è/', '/ù/', '/ú/', '/’/');
    $replace = array('e', 'c', 'a', 'e', 'o', 'o', 'e', 'u', 'u', '');
    $alias = preg_replace($search, $replace, $alias);
    $alias = 'news-bulletin/' . $alias;

    $sql = "SELECT * from {url_alias} as ua where ua.source like '" . $source . "' and ua.langcode like '" . $language . "'";
    //drush_print($sql);

    $result = $this->connection->query($sql); // TODO , fix this.
    $count = 0;
    foreach ($result as $row) {
      $count++;
    }

    if ($count >= 1) {
      \Drupal::messenger()->addMessage('success: url-alias for ' . $language . ' ' . $alias . ' is already found, good job', 'status', TRUE);
    }

    if ($count == 0) {
      // The Drupal 8 way of creating an alias:
      $aliasService = \Drupal::service('entity_type.manager')->getStorage('path_alias');
      $aliasService->save($source, '/' . $alias, $language);
      $count++;
      \Drupal::messenger()->addMessage("success: added url_alias for source: $source to alias: /$alias ", 'status', TRUE);
    }
  }


}

