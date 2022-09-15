<?php

namespace Drupal\idol_feed_api\Controller;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;

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
    $query = \Drupal::entityQuery('node');
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
          if ($node->bundle() == 'dir_listing') {
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
          $documentXml->addChild('AGRISOURCE_META_COVERAGEIDS');
          $documentXml->addChild('AGRISOURCE_META_DCCREATOR', $dcterms_creator);

          $targetIds = $node->get('field_meta_type')->getValue();
          $AGRISOURCE_META_AAFCTYPES_id = $this->getTargetIdsAsString($targetIds);
          $documentXml->addChild('AGRISOURCE_META_AAFCTYPEIDS', $AGRISOURCE_META_AAFCTYPES_id);

          if ($keywords == "") {
            $keywords = $node->get('field_keywords')->value;
          }
          if (is_null($keywords)) {
            $keywords = '';
          }
          $documentXml->addChild('AGRISOURCE_META_KEYWORDS', htmlspecialchars($keywords));

          $meta_subjects_and_types = $this->getTermLablesByTargetIds($targetIds, "en");
          if (is_null($meta_subjects_and_types)) {
            $meta_subjects_and_types = '';
          }
          $documentXml->addChild('AGRISOURCE_META_AAFCTYPES', $meta_subjects_and_types);

          $targetIds = $node->get('field_subject')->getValue();
          $documentXml->addChild('AGRISOURCE_META_SUBJECTS', $meta_subjects_and_types);
          $AGRISOURCE_META_SUBJECTS_id = $this->getTargetIdsAsString($targetIds);

          $targetIds = $node->get('layout_selection')->getValue();
          $documentXml->addChild('AGRISOURCE_META_PRESENTATIONTEMPLATETYPE', $meta_subjects_and_types);

          $documentXml->addChild('AGRISOURCE_META_COVERAGES');
          $documentXml->addChild('AGRISOURCE_META_DESCRIPTION', $dcterms_description);
          $documentXml->addChild('AGRISOURCE_META_SUBJECTIDS', $AGRISOURCE_META_SUBJECTS_id);

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
            $documentXmlFr->addChild('AGRISOURCE_META_COVERAGEIDS');
            $documentXmlFr->addChild('AGRISOURCE_META_DCCREATOR', $dcterms_creator);
            $targetIds = $node->get('field_meta_type')->getValue();
            $AGRISOURCE_META_AAFCTYPES_id = $this->getTargetIdsAsString($targetIds);
            $documentXmlFr->addChild('AGRISOURCE_META_AAFCTYPEIDS', $AGRISOURCE_META_AAFCTYPES_id);

            if ($keywords == "") {
              $keywords = $trnode->get('field_keywords')->value;
            }
            if (is_null($keywords)) {
              $keywords = '';
            }
            $documentXmlFr->addChild('AGRISOURCE_META_KEYWORDS', htmlspecialchars($keywords));

            $meta_subjects_and_types_fr = $this->getTermLablesByTargetIds($targetIds, "fr");
            if (is_null($meta_subjects_and_types_fr)) {
              $meta_subjects_and_types_fr = '';
            }
            $documentXmlFr->addChild('AGRISOURCE_META_AAFCTYPES', $meta_subjects_and_types_fr);

            $targetIds = $trnode->get('field_subject')->getValue();
            $AGRISOURCE_META_SUBJECTS_id = $this->getTargetIdsAsString($targetIds);
            $documentXmlFr->addChild('AGRISOURCE_META_SUBJECTS', $meta_subjects_and_types_fr);

            $targetIds = $node->get('layout_selection')->getValue();
            $documentXmlFr->addChild('AGRISOURCE_META_PRESENTATIONTEMPLATETYPE', $meta_subjects_and_types_fr);

            $documentXmlFr->addChild('AGRISOURCE_META_COVERAGES');
            $documentXmlFr->addChild('AGRISOURCE_META_DESCRIPTION', $dcterms_description);
            $documentXmlFr->addChild('AGRISOURCE_META_SUBJECTIDS', $AGRISOURCE_META_SUBJECTS_id);

            $content = $trnode->get('body')->value;
            $content = htmlentities($content, ENT_XML1, 'UTF-8');

            // $trnode->get('body')->value);
            $documentXmlFr->addChild('CONTENT', $content);
            $documentXmlFr->addChild('EXTERNALURL');
            $documentXmlFr->addChild('LANG', "fr");
            $documentXmlFr->addChild('SHORTTITLE', $trnode->get('title')->value);
            $documentXmlFr->addChild('TITLE', $trnode->get('title')->value);
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
    $documentXml->addChild('EMAIL', $node->get('field_email')->value);
    $documentXml->addChild('LOCATION', $node->get('field_locations')->value);
    $documentXml->addChild('NAME', $node->get('field_name')->value);
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
    $documentXml->addChild('AUDIENCEID');

    $categorytypeid = "";
    if ($node->get('field_newstype') && $node->get('field_newstype')->first()) {
      $newstypeId = $node->get('field_newstype')->first()->getValue()["target_id"];
      $term_name = trim(strtolower(Term::load($newstypeId)->label()));
      switch ($term_name) {
        case "deputy ministers' messages":
          $categorytypeid = '1308334182983';
          break;

        case "charting the way forward":
          $categorytypeid = '1591971211315';
          break;

        case "pay and benefits":
          $categorytypeid = '1508942999196';
          break;

        case "general":
          $categorytypeid = '1308325693175';
          break;

        case "across the public service":
          $categorytypeid = '1508942999198';
          break;

        case "events":
          $categorytypeid = '1308334182981';
          break;

        case "gcwcc":
          $categorytypeid = '1379951494338';
          break;

        case "professional development":
          $categorytypeid = '1508942999197';
          break;

        case "isb service notices":
          $categorytypeid = '1308334182982';
          break;

        default:
          $categorytypeid = $term_name;
          break;
        // End of category type id mapping.
      }
    }
    $documentXml->addChild('CATEGORYID', $categorytypeid);
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
      if ($targetId["target_id"] > 0) {
        if (!in_array($targetId["target_id"], $array_of_all_tids)) {
          continue;
        }
        if ($lang == "fr" && Term::load($targetId["target_id"])->hasTranslation('fr')) {
          $term_name = Term::load($targetId["target_id"])->getTranslation('fr')->label();
          if (!empty($label)) {
            $label = $label . ";";
          }
          $label = $label . $term_name;
          // $term_name = $term->label();
          // $label = $label . $term_name . ";";
        }
        else {
          $term = Term::load($targetId["target_id"]);
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
