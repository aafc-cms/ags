<?php

namespace Drupal\agri_admin\Plugin\Block;


use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;


/**
 * Provides an employment classification groups block.
 *
 * @Block(
 *   id = "employment_classification_groups",
 *   admin_label = @Translation("Employment Classification Groups Display"),
 * )
 */
class  EmploymentClassificationGroups extends BlockBase {

  protected function getNode() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      if (is_string($node) && is_numeric($node)) {
        $nid = $node;
        $vid = NULL;
        $node = self::_latest_revision($nid, $vid);
      }
      else if (gettype($node) == 'object') {
        $nid = $node->id();
      }
      if ($node->getType() == 'empl') {
        return $node;
      }
    }
    return false;
  }

 /**
  * {@inheritdoc}
  */
  public function build() {
    $config = $this->getConfiguration(); 
    $block['#id'] = $config['id'];
    $block['#title'] = 'Employment Classification Groups';
    $block['content']['results']['#type'] = 'markup';

    $current_language = Drupal::languageManager()->getCurrentLanguage();
    $currentlangId    = $current_language->getId();
    $classification   = '';

    $emplNode =  $this->getNode();
    if (!$emplNode) {
      $block['content']['#markup'] = $classification;
      return $block;
    }

    if ($paragraph_field_items = $emplNode->get('field_classification')->getValue()) {
      // Get storage. It very useful for loading a small number of objects.
      $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
      // Collect paragraph field's ids.
      $ids = array_column($paragraph_field_items, 'target_id');
      // Load all paragraph objects.
      $paragraphs_objects = $paragraph_storage->loadMultiple($ids);
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      foreach ($paragraphs_objects as $paragraph) {
        // Get field from the paragraph.
        $groupval    = $paragraph->get('field_class_id')->value;
        $subgroupval = $paragraph->get('field_class_sub')->value;
        $levelval    = $paragraph->get('field_class_level')->value;
        $tmpText     = '';
        // Do something with $text...
        if ($subgroupval) {
          $tmpText = $groupval.'-'.$subgroupval.'-'.$levelval;
        }
        else {
          $tmpText = $groupval.'-'.$levelval;
        }

        if ( strlen($classification) >0 ) {
            $classification = $classification.', '. $tmpText;
        }
        else {
          $classification = $tmpText;
        }
      }

      // get equivalent flag value
      $equivalentval = $emplNode->get('field_and_equivalent')->getValue();
      //$equivalentlbl = $emplNode->get('field_and_equivalent')->getFieldDefinition()->getLabel();
      if ($equivalentval) {
        if ($currentlangId == 'en') {
          $classification = $classification.' and equivalent';
        }
        else {
          $classification = $classification.' et équivalent';
        }
      }
    }

    $classification = '<div class="field--items">'.$classification.'</div>';

    $block['content']['#markup'] = $classification;
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * Get the latest revision.
   */
  public static function _latest_revision($nid, &$vid) {
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $otherLang = 'fr';
    if ($lang == 'fr') {
      $otherLang = 'en';
    }
    $latestRevisionResult = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->latestRevision()
      ->condition('nid', $nid, '=')
      ->execute();
    if (count($latestRevisionResult)) {
      $node_revision_id = key($latestRevisionResult);
      if ($node_revision_id == $vid) {
        // There is no pending revision, the current revision is the latest.
        return FALSE;
      }
      $vid = $node_revision_id;
      $latestRevision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($node_revision_id);
      if ($latestRevision->language()->getId() != $lang) {
        $latestRevision = $latestRevision->getTranslation($lang);
      }
      $moderation_state = $latestRevision->get('moderation_state')->getString();
      if ($moderation_state == 'draft') {
        return $latestRevision;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $config = $this->getConfiguration();
    return $form;
  }
	
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['employment_classification_groups'] =
    $formState->getValue('employment_classification_groups');
  }

}
