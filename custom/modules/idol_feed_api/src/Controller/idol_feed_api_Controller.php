<?php

/**
 * @file
 * Contains \Drupal\test_api\Controller\TestAPIController.
 */

namespace Drupal\idol_feed_api\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\SimpleXmlElement;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;

use Drupal\Core\Url;


/**
 * Controller routines for idol_feed_api_Controller routes.
 */
class idol_feed_api_Controller extends ControllerBase {


  public function get_content( Request $request ) {
    //

    $requstURL = $request->getSchemeAndHttpHost();

    // echo $requstURL . "\n";
    $xml = new \SimpleXmlElement("<DOCUMENTS></DOCUMENTS>");
    $query = \Drupal::entityQuery('node');
    // $query->condition('type', 'page');
    $query->condition('status', 1);

    $sinceDate = $request->get('sinceDate');
    if(isset($sinceDate) && !(trim($sinceDate) === '')){
      $date = DrupalDateTime::createFromTimestamp($sinceDate);
      // echo $date . "\n";
      $date = $date->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      // echo $date . "\n";
      $query->condition('field_modified', $date, '>=');
    }
    $results = $query->execute();

    if (!empty($results)) {

      if(!empty($entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($results))) {
        foreach ($entities as $node) {

          //eng node
          $documentXml = $xml->addChild('DOCUMENT');
          // $nodeurl = url(drupal_get_path_alias('node/' . $node->nid), array('absolute' => TRUE));
          // $nodeurl = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
         $documentXml->addChild('URI',$requstURL . $node->toUrl()->toString());
         $datatype = $node->type->entity->label();
         switch (strtolower($datatype)) {
           case "page de base":
           case "basic page":
           case "page de destination":
           case "landing page":
            $datatype = "gene-gene";
            break;
           case "titre du poste":
           case "employment opportunity":

             $datatype = "empl-empl";
             break;
           case "nouvelle":
           case "news":
               $datatype = "news-nouv";
               break;
         }
         $documentXml->addChild('DATATYPE',$datatype);

         $metatags = metatag_generate_entity_metatags($node);
         $dcterms_creator="";
         $dcterms_description="";
         $keywords="";
         foreach ($metatags as $key => $value) {
           switch (strtolower($key)) {
             case "dcterms_creator":
                $dcterms_creator = $value["#attributes"]["content"];
                break;
              case "dcterms_description":
                $dcterms_description = $value["#attributes"]["content"];
                break;
              case "keywords":
                $keywords = $value["#attributes"]["content"];

            }
          }

         $documentXml->addChild('CONTENTTYPE',"text/html");
         $documentXml->addChild('FILE_SYSTEM_MODIFIED_DATE',$node->get('field_modified')->getValue()[0]['value'] );
         $documentXml->addChild('META_MODIFIED_DATE',$node->get('field_modified')->getValue()[0]['value'] );
         $documentXml->addChild('AGRISOURCE_META_DATEMODIFIED',$node->get('field_modified')->getValue()[0]['value'] );
         $documentXml->addChild('AGRISOURCE_META_COVERAGEIDS');
         $documentXml->addChild('AGRISOURCE_META_DCCREATOR',$dcterms_creator);


         $targetIds = $node->get('field_meta_type')->getValue();
         $AGRISOURCE_META_AAFCTYPES_id = $this->getTargetIdsAsString($targetIds);
         $documentXml->addChild('AGRISOURCE_META_AAFCTYPEIDS', $AGRISOURCE_META_AAFCTYPES_id);

         if($keywords==""){
           $keywords = $node->get('field_keywords')->value;
         }
         $documentXml->addChild('AGRISOURCE_META_KEYWORDS', $keywords);

         $documentXml->addChild('AGRISOURCE_META_AAFCTYPES', $this->getTermLablesByTargetIds($targetIds, "en"));

         $targetIds = $node->get('field_subject')->getValue();
         $documentXml->addChild('AGRISOURCE_META_SUBJECTS', $this->getTermLablesByTargetIds($targetIds, "en"));
         $AGRISOURCE_META_SUBJECTS_id = $this->getTargetIdsAsString($targetIds);

         $targetIds = $node->get('layout_selection')->getValue();
         $documentXml->addChild('AGRISOURCE_META_PRESENTATIONTEMPLATETYPE', $this->getTermLablesByTargetIds($targetIds, "en"));

         $documentXml->addChild('AGRISOURCE_META_COVERAGES');
         $documentXml->addChild('AGRISOURCE_META_DESCRIPTION', $dcterms_description);
         $documentXml->addChild('AGRISOURCE_META_SUBJECTIDS', $AGRISOURCE_META_SUBJECTS_id);

         $content = $node->get('body')->value;
         $content = htmlentities($content, ENT_XML1, 'UTF-8');
         $documentXml->addChild('CONTENT', $content);//$node->get('body')->value);
         $documentXml->addChild('EXTERNALURL');
         $documentXml->addChild('LANG', "en");
         $documentXml->addChild('SHORTTITLE', $node->get('title')->value);
         $documentXml->addChild('TITLE', $node->get('title')->value);
         if($datatype=="empl-empl"){
           $this->addElementForEmpl($documentXml, $node, "en");

         }elseif($datatype=="news-nouv"){
           $this->addElementForNews($documentXml, $node, "en");
         }

         //French node
        if ($node->hasTranslation("fr")) {
           $trnode = $node->getTranslation('fr');
           $documentXmlFr = $xml->addChild('DOCUMENT');
           // $nodeurl = url(drupal_get_path_alias('node/' . $node->nid), array('absolute' => TRUE));
           // $nodeurl = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
          $documentXmlFr->addChild('URI',$requstURL . $trnode->toUrl()->toString());
          //$DATATYPE = $trnode->type->entity->label();

          $documentXmlFr->addChild('DATATYPE',$datatype);

          $metatags = metatag_generate_entity_metatags($trnode);
          $dcterms_creator="";
          $dcterms_description="";
          $keywords="";
          foreach ($metatags as $key => $value) {
            switch (strtolower($key)) {
              case "dcterms_creator":
                 $dcterms_creator = $value["#attributes"]["content"];
                 break;
               case "dcterms_description":
                 $dcterms_description = $value["#attributes"]["content"];
                 break;
               case "keywords":
                 $keywords = $value["#attributes"]["content"];
             }
           }

          $documentXmlFr->addChild('CONTENTTYPE',"text/html");
          $documentXmlFr->addChild('FILE_SYSTEM_MODIFIED_DATE',$trnode->get('field_modified')->getValue()[0]['value'] );
          $documentXmlFr->addChild('META_MODIFIED_DATE',$trnode->get('field_modified')->getValue()[0]['value'] );
          $documentXmlFr->addChild('AGRISOURCE_META_DATEMODIFIED',$trnode->get('field_modified')->getValue()[0]['value'] );
          $documentXmlFr->addChild('AGRISOURCE_META_COVERAGEIDS');
          $documentXmlFr->addChild('AGRISOURCE_META_DCCREATOR',$dcterms_creator);
          $targetIds = $node->get('field_meta_type')->getValue();
          $AGRISOURCE_META_AAFCTYPES_id = $this->getTargetIdsAsString($targetIds);
          $documentXmlFr->addChild('AGRISOURCE_META_AAFCTYPEIDS', $AGRISOURCE_META_AAFCTYPES_id);

          if($keywords==""){
            $keywords = $trnode->get('field_keywords')->value;
          }
          $documentXmlFr->addChild('AGRISOURCE_META_KEYWORDS', $keywords);

          $documentXmlFr->addChild('AGRISOURCE_META_AAFCTYPES', $this->getTermLablesByTargetIds($targetIds, "fr"));

          $targetIds = $trnode->get('field_subject')->getValue();
          $AGRISOURCE_META_SUBJECTS_id = $this->getTargetIdsAsString($targetIds);
          $documentXmlFr->addChild('AGRISOURCE_META_SUBJECTS', $this->getTermLablesByTargetIds($targetIds, "fr"));

          $targetIds = $node->get('layout_selection')->getValue();
          $documentXmlFr->addChild('AGRISOURCE_META_PRESENTATIONTEMPLATETYPE', $this->getTermLablesByTargetIds($targetIds, "fr"));

          $documentXmlFr->addChild('AGRISOURCE_META_COVERAGES');
          $documentXmlFr->addChild('AGRISOURCE_META_DESCRIPTION', $dcterms_description);
          $documentXmlFr->addChild('AGRISOURCE_META_SUBJECTIDS', $AGRISOURCE_META_SUBJECTS_id);

          $content = $trnode->get('body')->value;
          $content = htmlentities($content, ENT_XML1, 'UTF-8');

          $documentXmlFr->addChild('CONTENT', $content);//$trnode->get('body')->value);
          $documentXmlFr->addChild('EXTERNALURL');
          $documentXmlFr->addChild('LANG', "fr");
          $documentXmlFr->addChild('SHORTTITLE', $trnode->get('title')->value);
          $documentXmlFr->addChild('TITLE', $trnode->get('title')->value);
          if($datatype=="empl-empl"){
            $this->addElementForEmpl($documentXmlFr, $trnode, "fr");

          }elseif($datatype=="news-nouv"){
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

  public function addElementForEmpl($documentXml, $node, $lang){
    $documentXml->addChild('ANDEQUIVALENT');
    $targetIds = $node->get('field_branch')->getValue();
    $BRANCH = $this->getTermLablesByTargetIds($targetIds, $lang);
    $documentXml->addChild('BRANCH', $BRANCH);

    $classificationXml = $documentXml->addChild('CLASSIFICATIONS');

    $my_paragraphs = $node->get('field_classification')->getValue();
    $group = "";
    $level = "";
    $subGroup = "";
    $groupArray = array();
    $levelArray = array();
    $subGroupArray = array();
    foreach ($my_paragraphs as $item) {
      $paragraph = \Drupal\paragraphs\Entity\Paragraph::load($item['target_id']);
      if (!$paragraph->field_class_id->isEmpty()) {
          $field_class_id = $paragraph->get('field_class_id')->first()->getValue()["value"];
          if (!in_array($field_class_id, $groupArray)){
            if(!empty($group))
              $group = $group . ";";
            $group = $group . $field_class_id ;
            $groupArray[] = $field_class_id;
          }
      }
      if (!$paragraph->field_class_level->isEmpty()) {
        $field_class_level = $paragraph->get('field_class_level')->first()->getValue()["value"];
        if (!in_array($field_class_level, $levelArray)){
          if(!empty($level))
            $level = $level . ";";
          $level = $level . $field_class_level;
          $levelArray[] = $field_class_level;
        }
      }
      if (!$paragraph->field_class_sub->isEmpty()) {

        $field_class_sub = $paragraph->get('field_class_sub')->first()->getValue()["value"];
        if (!in_array($field_class_sub, $subGroupArray)){
          if(!empty($subGroup))
            $subGroup = $subGroup . ";";
          $subGroup = $subGroup . $field_class_sub;
          $subGroupArray[] = $field_class_sub;
        }
      }

    }
    $classificationXml->addChild('GROUP', $group);
    $classificationXml->addChild('LEVEL', $level);
    $classificationXml->addChild('SUBGROUP', $subGroup);

    $documentXml->addChild('CLOSINGDATE', $node->get('field_date_closing')->getValue()[0]['value']);
    $documentXml->addChild('CLOSINGTIME');
    $documentXml->addChild('EMAIL', $node->get('field_email')->value );
    $documentXml->addChild('LOCATION', $node->get('field_locations')->value );
    $documentXml->addChild('NAME', $node->get('field_name')->value );
    $documentXml->addChild('OPENTO', $node->get('field_open_to')->value );
    $documentXml->addChild('POSITIONTITLE' );

    $targetIds = $node->get('field_type')->getValue();
    $TYPEOFADVERTISEMENT = $this->getTermLablesByTargetIds($targetIds, $lang);
    $documentXml->addChild('TYPEOFADVERTISEMENT', $TYPEOFADVERTISEMENT);


  }

  public function  addElementForNews($documentXml, $node, $lang){
    $documentXml->addChild('ANDEQUIVALENT');
    $documentXml->addChild('AUDIENCEID');

    $categorytypeid = "";
    if($node->get('field_newstype') && $node->get('field_newstype')->first()){
      $newstypeId = $node->get('field_newstype')->first()->getValue()["target_id"];

      switch ($newstypeId) {
          case 21:
                $categorytypeid = '1308325693175';
                break;
          case 22:
                $categorytypeid = '1508942999196';
                break;
          case 23:
                $categorytypeid = '1508942999197';
                break;
          case 24:
                $categorytypeid = '1308334182981';
                break;
          case 25:
                $categorytypeid = '1308334182982';
                break;
          case 26:
                $categorytypeid = '1308334182983';
                break;
          case 27:
                $categorytypeid = '1508942999198';
                break;
          case 28:
                $categorytypeid = '1379951494338';
                break;
          //End of category type id mapping
        }
      }
      $documentXml->addChild('CATEGORYID', $categorytypeid);
      $documentXml->addChild('COMMUNICATIONADVISOREMAIL', $node->get('field_newsreviewedby')->value );
      $documentXml->addChild('DIRECTORADVISOREMAIL', $node->get('field_newsapprovedby')->value );
      $documentXml->addChild('PUBLISHDATE', $node->get('field_posted')->getValue()[0]['value']);
      $documentXml->addChild('SENTBY', $node->get('field_from')->value);
      $documentXml->addChild('TRANSLATIONREQUESTNUMBER', $node->get('field_newstranslationnum')->value);
  }

  public function getTargetIdsAsString( $targetIds ) {
    if(!isset($targetIds)){
      return "";
    }

    $label = "";
    foreach ($targetIds as $targetId) {
      if( $targetId["target_id"] > 0 ){
        $label = $label . $targetId["target_id"] . ";";
      }
    }

    return $label;
  }


public function getTermLablesByTargetIds( $targetIds, $lang ) {
  if(!isset($targetIds)){
    return "";
  }

  $label = "";
  foreach ($targetIds as $targetId) {
    if( $targetId["target_id"] > 0 ){
        if($lang=="fr" && Term::load($targetId["target_id"])->hasTranslation('fr')){
          $term_name = \Drupal\taxonomy\Entity\Term::load($targetId["target_id"])->getTranslation('fr')->label();
          if(!empty($label))
            $label = $label . ";";
          $label = $label . $term_name;
          // $term_name = $term->label();
          // $label = $label . $term_name . ";";
        }else {
          $term_name = \Drupal\taxonomy\Entity\Term::load($targetId["target_id"])->label();
          // $term_name = $term->label();
          if(!empty($label))
            $label = $label . ";";
          $label = $label . $term_name;
        }

    }
  }

  return $label;
}

}
