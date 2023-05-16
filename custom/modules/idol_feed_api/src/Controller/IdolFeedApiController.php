<?php

namespace Drupal\idol_feed_api\Controller;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;
use Drupal\agri_admin\AgriAdminHelper;

/**
 * Controller routines for IdolFeedApiController routes.
 */
class IdolFeedApiController extends ControllerBase {

  /**
   * Retrieve the content that we want autonomy idol to index.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \SimpleXmlElement
   *   The xml formatted document.
   */
  public function getContent(Request $request) {


    $requstURL = $request->getSchemeAndHttpHost();

    // Echo $requstURL . "\n";.
    $xml = new \SimpleXmlElement("<DOCUMENTS></DOCUMENTS>");
    $query = \Drupal::entityQuery('node')->accessCheck(FALSE);
    // $query->condition('type', 'page');
    $query->condition('status', 1);

    $sinceDate = $request->get('sinceDate');
    if (isset($sinceDate) && !(trim($sinceDate) === '')) {
      $date = DrupalDateTime::createFromTimestamp($sinceDate);
      // Echo $date . "\n";.
      $date = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      // Echo $date . "\n";.
      $query->condition('field_modified', $date, '>=');
    }
    $results = $query->execute();

    if (!empty($results)) {

      if (!empty($entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($results))) {
        global $array_of_all_tids;
//        $array_of_all_tids = [];
        if (!isset($array_of_all_tids)) {
          $connection = \Drupal\Core\Database\Database::getConnection();
          $myselect = $query = $connection->select('taxonomy_term_data', 'ttd')
            ->orderBy('tid', 'DESC')
            ->fields('ttd', ['tid'])->execute();
          $tids = [];
          while ($row = $myselect->fetchAssoc()) {
            // Do something with:
            $tids[] = $row['tid'];
          }
          $array_of_all_tids = $tids;
        }

        foreach ($entities as $node) {
          $tmp_field_modified = date('Y-m-d');
          if ($node->bundle() == 'dir_listing' || $node->bundle() == 'webform') {
            continue;
          }

          // Eng node.
          $documentXml = $xml->addChild('DOCUMENT');
          // $nodeurl = url(drupal_get_path_alias('node/' . $node->nid), array('absolute' => TRUE));
          // $nodeurl = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
          $documentXml->addChild('URI', $requstURL . $node->toUrl()->toString());
          $datatype = $node->type->entity->label();
          switch (strtolower($datatype)) {
            case "page de base":
            case "basic page":
            case "page de destination":
            case "landing page":
            case "page interne":
            case "internal page":
              $datatype = "gene-gene";
              break;

            case "titre du poste":
            case "employment opportunity":
            case "opportunité d'emploi":

              $datatype = "empl-empl";
              break;

            case "nouvelle":
            case "news":
              $datatype = "news-nouv";
              break;
          }
          $documentXml->addChild('DATATYPE', $datatype);

          $metatags = metatag_generate_entity_metatags($node);
          $dcterms_creator = "";
          $dcterms_description = "";
          $keywords = "";
          foreach ($metatags as $key => $value) {
            switch (strtolower($key)) {
              case "dcterms_creator":
                $dcterms_creator = $value["#attributes"]["content"];
                break;

              case "dcterms_description":
                $dcterms_description = $value["#attributes"]["content"];
                $dcterms_description = htmlentities($dcterms_description, ENT_XML1, 'UTF-8');
                break;

              case "keywords":
                $keywords = $value["#attributes"]["content"];

            }
          }

          if (isset($node->get('field_modified')->getValue()[0]['value'])) {
            $tmp_field_modified = $node->get('field_modified')->getValue()[0]['value'];
          }
          $documentXml->addChild('CONTENTTYPE', "text/html");
          $documentXml->addChild('FILE_SYSTEM_MODIFIED_DATE', $tmp_field_modified);
          $documentXml->addChild('META_MODIFIED_DATE', $tmp_field_modified);
          $documentXml->addChild('AGRISOURCE_META_DATEMODIFIED', $tmp_field_modified);
          $documentXml->addChild('AGRISOURCE_META_DCCREATOR', $dcterms_creator);

          if ($keywords == "") {
            $keywords = $node->get('field_keywords')->value;
          }
          if (is_null($keywords)) {
            $keywords = '';
          }
          $documentXml->addChild('AGRISOURCE_META_KEYWORDS', htmlspecialchars($keywords));

          $targetIds = $node->get('field_meta_type')->getValue();
          $meta_types = $this->getTermLablesByTargetIds($targetIds, "en");
          if (is_null($meta_types)) {
            $meta_types = '';
          }
          $documentXml->addChild('AGRISOURCE_META_AAFCTYPES', $meta_types);

          $targetIds = $node->get('field_subject')->getValue();
          $meta_subjects = $this->getTermLablesByTargetIds($targetIds, "en");
          if (is_null($meta_subjects)) {
            $meta_subjects = '';
          }
          $documentXml->addChild('AGRISOURCE_META_SUBJECTS', $meta_subjects);

          $targetIds = $node->get('layout_selection')->getValue();
          $meta_layout = $this->getTermLablesByTargetIds($targetIds, "en");
          if (is_null($meta_layout)) {
            $meta_layout = '';
          }
          $documentXml->addChild('AGRISOURCE_META_PRESENTATIONTEMPLATETYPE', $meta_layout);

          $documentXml->addChild('AGRISOURCE_META_DESCRIPTION', $dcterms_description);

          $content = $node->get('body')->value;
          $content = htmlentities($content, ENT_XML1, 'UTF-8');
          // $node->get('body')->value);
          $documentXml->addChild('CONTENT', $content);
          $documentXml->addChild('EXTERNALURL');
          $documentXml->addChild('LANG', "en");
          $title_special_chars = htmlspecialchars($node->get('title')->value);
          $documentXml->addChild('SHORTTITLE', $title_special_chars);
          $documentXml->addChild('TITLE', $title_special_chars);
          if ($datatype == "empl-empl") {
            $this->addElementForEmpl($documentXml, $node, "en");

          }
          elseif ($datatype == "news-nouv") {
            $this->addElementForNews($documentXml, $node, "en");
          }

          // French node.
          if ($node->hasTranslation("fr")) {
            $trnode = $node->getTranslation('fr');
            $documentXmlFr = $xml->addChild('DOCUMENT');
            // $nodeurl = url(drupal_get_path_alias('node/' . $node->nid), array('absolute' => TRUE));
            // $nodeurl = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
            $documentXmlFr->addChild('URI', $requstURL . $trnode->toUrl()->toString());
            // $DATATYPE = $trnode->type->entity->label();
            $documentXmlFr->addChild('DATATYPE', $datatype);

            $metatags = metatag_generate_entity_metatags($trnode);
            $dcterms_creator = "";
            $dcterms_description = "";
            $keywords = "";
            foreach ($metatags as $key => $value) {
              switch (strtolower($key)) {
                case "dcterms_creator":
                  $dcterms_creator = $value["#attributes"]["content"];
                  break;

                case "dcterms_description":
                  $dcterms_description = $value["#attributes"]["content"];
                  $dcterms_description = htmlentities($dcterms_description, ENT_XML1, 'UTF-8');
                  break;

                case "keywords":
                  $keywords = $value["#attributes"]["content"];
              }
            }

            $documentXmlFr->addChild('CONTENTTYPE', "text/html");
            $documentXmlFr->addChild('FILE_SYSTEM_MODIFIED_DATE', $tmp_field_modified);
            $documentXmlFr->addChild('META_MODIFIED_DATE', $tmp_field_modified);
            $documentXmlFr->addChild('AGRISOURCE_META_DATEMODIFIED', $tmp_field_modified);
//            $documentXmlFr->addChild('AGRISOURCE_META_COVERAGEIDS');    --- removing all IDs for Federated Search
            $documentXmlFr->addChild('AGRISOURCE_META_DCCREATOR', $dcterms_creator);

            if ($keywords == "") {
              $keywords = $trnode->get('field_keywords')->value;
            }
            if (is_null($keywords)) {
              $keywords = '';
            }
            $documentXmlFr->addChild('AGRISOURCE_META_KEYWORDS', htmlspecialchars($keywords));

            $targetIds = $node->get('field_meta_type')->getValue();
            $meta_types_fr = $this->getTermLablesByTargetIds($targetIds, "fr");
            if (is_null($meta_types_fr)) {
              $meta_types_fr = '';
            }
            $documentXmlFr->addChild('AGRISOURCE_META_AAFCTYPES', $meta_types_fr);

            $targetIds = $trnode->get('field_subject')->getValue();
            $meta_subjects_fr = $this->getTermLablesByTargetIds($targetIds, "fr");
            if (is_null($meta_subjects_fr)) {
              $meta_subjects_fr = '';
            }
            $documentXmlFr->addChild('AGRISOURCE_META_SUBJECTS', $meta_subjects_fr);

            $targetIds = $node->get('layout_selection')->getValue();
            $meta_layout_fr = $this->getTermLablesByTargetIds($targetIds, "fr");
            if (is_null($meta_layout_fr)) {
              $meta_layout_fr = '';
            }
            $documentXmlFr->addChild('AGRISOURCE_META_PRESENTATIONTEMPLATETYPE', $meta_layout_fr);

            $documentXmlFr->addChild('AGRISOURCE_META_DESCRIPTION', $dcterms_description);

            $content = $trnode->get('body')->value;
            $content = htmlentities($content, ENT_XML1, 'UTF-8');

            // $trnode->get('body')->value);
            $documentXmlFr->addChild('CONTENT', $content);
            $documentXmlFr->addChild('EXTERNALURL');
            $documentXmlFr->addChild('LANG', "fr");
            $title_special_chars_fr = htmlspecialchars($trnode->get('title')->value);
            $documentXmlFr->addChild('SHORTTITLE', $title_special_chars_fr);
            $documentXmlFr->addChild('TITLE', $title_special_chars_fr);
            if ($datatype == "empl-empl") {
              $this->addElementForEmpl($documentXmlFr, $trnode, "fr");

            }
            elseif ($datatype == "news-nouv") {
              $this->addElementForNews($documentXmlFr, $trnode, "fr");
            }

          }

        }
      }
    }

    // $a = htmlentities($xml->asXML());
    // $b = html_entity_decode($xml->asXML(),ENT_QUOTES | ENT_HTML401, 'ISO-8859-1');
    $response = new Response($xml->asXML());
    // $response = new Response(html_entity_decode(utf8_decode($xml->asXML())));
    $response->headers->set('Content-Type', 'xml');

    return $response;
  }

  /**
   * Element for employment type content.
   */
  public function addElementForEmpl($documentXml, $node, $lang) {

    $targetIds = $node->get('field_branch')->getValue();
    $BRANCH = $this->getTermLablesByTargetIds($targetIds, $lang);
    $documentXml->addChild('BRANCH', $BRANCH);

    // $classificationXml = $documentXml->addChild('CLASSIFICATIONS');
    $andEquivalent = $node->get('field_and_equivalent')->getValue()['0']['value'];
    if ($andEquivalent == 1) {
      $documentXml->addChild('ANDEQUIVALENT', 'yes');
    }
    else {
      $documentXml->addChild('ANDEQUIVALENT');
    }

    $my_paragraphs = $node->get('field_classification')->getValue();
    $group = "";
    $level = "";
    $subGroup = "";
    // $groupArray = array();
    // $levelArray = array();
    // $subGroupArray = array();
    foreach ($my_paragraphs as $item) {
      $paragraph = Paragraph::load($item['target_id']);
      $classificationXml = $documentXml->addChild('CLASSIFICATIONS');
      if (!$paragraph->field_class_id->isEmpty()) {
        $field_class_id = $paragraph->get('field_class_id')->first()->getValue()["value"];
        $classificationXml->addChild('GROUP', $field_class_id);
      }
      else {
        $classificationXml->addChild('GROUP');
      }
      if (!$paragraph->field_class_level->isEmpty()) {
        $field_class_level = $paragraph->get('field_class_level')->first()->getValue()["value"];
        $classificationXml->addChild('LEVEL', $field_class_level);
      }
      else {
        $classificationXml->addChild('LEVEL');
      }
      if (!$paragraph->field_class_sub->isEmpty()) {

        $field_class_sub = $paragraph->get('field_class_sub')->first()->getValue()["value"];
        $classificationXml->addChild('SUBGROUP', $field_class_sub);
      }
      else {
        $classificationXml->addChild('SUBGROUP');
      }

    }
    // $classificationXml->addChild('GROUP', $group);
    // $classificationXml->addChild('LEVEL', $level);
    // $classificationXml->addChild('SUBGROUP', $subGroup);
    $documentXml->addChild('CLOSINGDATE', $node->get('field_date_closing')->getValue()[0]['value']);
    $documentXml->addChild('CLOSINGTIME');
    $documentXml->addChild('EMAIL', htmlspecialchars($node->get('field_email')->value));
    $documentXml->addChild('LOCATION', htmlspecialchars($node->get('field_locations')->value));
    $documentXml->addChild('NAME', htmlspecialchars($node->get('field_name')->value));
    $documentXml->addChild('OPENTO', $node->get('field_open_to')->value);
    $documentXml->addChild('POSITIONTITLE');

    $targetIds = $node->get('field_type')->getValue();
    $TYPEOFADVERTISEMENT = $this->getTermLablesByTargetIds($targetIds, $lang);
    $documentXml->addChild('TYPEOFADVERTISEMENT', $TYPEOFADVERTISEMENT);

  }

  /**
   * Element for employment type content.
   */
  public function addElementForNews($documentXml, $node, $lang) {
    $documentXml->addChild('ADDITIONALREMARKS');

    if ($node->get('field_newstype') && $node->get('field_newstype')->first()) {
      $newstypeId = $node->get('field_newstype')->first()->getValue()["target_id"];
      $term = Term::load($newstypeId);
      if ($lang == "en") {
        $term_name = trim($term->label());
      } elseif ($lang == "fr" && $term->hasTranslation('fr')) {
        $term_name = trim($term->getTranslation('fr')->label());
      }
    }
    $documentXml->addChild('NEWSCATEGORY', $term_name);

    if ($node->get('field_to') && $node->get('field_to')->first()) {
      $audienceId = $node->get('field_to')->value;
      if ($lang == "en") {
        if ($audienceId == "all") {
          $audience = "All staff";
        } elseif ($audienceId == "ncr") {
          $audience = "NCR only";
        } elseif ($audienceId == "other") {
          $audience = "Other regions (not NCR)";
        }
      } elseif ($lang == "fr") {
        if ($audienceId == "all") {
          $audience = "Tous les employés";
        } elseif ($audienceId == "ncr") {
          $audience = "RCN seulement";
        } elseif ($audienceId == "other") {
          $audience = "Régions autres que la RCN)";
        }
      }
    }
    $documentXml->addChild('AUDIENCE', $audience);
    $documentXml->addChild('COMMUNICATIONADVISOREMAIL', $node->get('field_newsreviewedby')->value);
    $documentXml->addChild('DIRECTORADVISOREMAIL', $node->get('field_newsapprovedby')->value);
    $documentXml->addChild('PUBLISHDATE', $node->get('field_posted')->getValue()[0]['value']);
    $documentXml->addChild('SENTBY', htmlspecialchars($node->get('field_from')->value));
    $documentXml->addChild('TRANSLATIONREQUESTNUMBER', htmlspecialchars($node->get('field_newstranslationnum')->value));
  }

  /**
   * Retrieve the target ids string label.
   */
  public function getTargetIdsAsString($targetIds) {
    if (!isset($targetIds)) {
      return "";
    }

    $label = "";
    foreach ($targetIds as $targetId) {
      if ($targetId["target_id"] > 0) {
        $label = $label . $targetId["target_id"] . ";";
      }
    }

    return $label;
  }

  /**
   * Retrieve the term labels.
   */
  public function getTermLablesByTargetIds($targetIds, $lang) {
    if (!isset($targetIds)) {
      return "";
    }

    global $array_of_all_tids;
    $label = "";
    foreach ($targetIds as $targetId) {
      if (is_numeric($targetId["target_id"]) and $targetId["target_id"] > 0) {
        if (!in_array($targetId["target_id"], $array_of_all_tids)) {
          continue;
        }
        $term = Term::load($targetId["target_id"]);
        if (is_null($term)) {
          $label = "";
          continue;
        }
        if ($lang == "fr" && $term->hasTranslation('fr')) {
          $term_name = $term->getTranslation('fr')->label();
          if (!empty($label)) {
            $label = $label . ";";
          }
          $label = $label . $term_name;
        }
        else {
          if ($term) {
            $term_name = $term->label();
            if (!empty($label)) {
              $label = $label . ";";
            }
            $label = $label . $term_name;
          }
        }

      }
    }
    return $label;
  }

}
