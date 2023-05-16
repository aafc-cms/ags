<?php

namespace Drupal\agri_admin\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an employment classification groups block.
 *
 * @Block(
 *   id = "employment_classification_groups",
 *   admin_label = @Translation("Employment Classification Groups Display"),
 * )
 */
class EmploymentClassificationGroups extends BlockBase {

  /**
   * Retrieve the node and set reference to param if it's a preview node.
   *
   * @param bool $ispreview_node
   *   Preview node flag by reference.
   *
   * @return mixed
   *   Return FALSE if there is no node otherwise the node object (revision id).
   */
  protected function getNode(&$ispreview_node) {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!is_object($node)) {
      $node = \Drupal::routeMatch()->getParameter('node_preview');
      $ispreview_node = TRUE;
    }
    if ($node) {
      if (is_string($node) && is_numeric($node)) {
        // Can be removed once we move to Drupal >= 8.6.0 , currently on 8.5.0.
        // See change record here: https://www.drupal.org/node/2942013 .
        $nid = $node;
        $vid = NULL;
        $node = self::latestRevision($nid, $vid);
      }
      elseif (gettype($node) == 'object') {
        $nid = $node->id();
      }
      if ($node->getType() == 'empl') {
        return $node;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $ispreview_node = FALSE;
    $config = $this->getConfiguration();
    $block['#id'] = $config['id'];
    $block['#title'] = 'Employment Classification Groups';
    $block['content']['results']['#type'] = 'markup';

    $current_language = \Drupal::languageManager()->getCurrentLanguage();
    $currentlangId    = $current_language->getId();
    $classification   = '';

    $emplNode = $this->getNode($ispreview_node);
    if (!$emplNode) {
      $block['content']['#markup'] = $classification;
      return $block;
    }

    if ($ispreview_node) {
      // Logic for preview node.
      $paragraphs_objects = $emplNode->get('field_classification')->referencedEntities();
    }
    else {
      $paragraph_field_items = $emplNode->get('field_classification')->getValue();
      $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
      // Collect paragraph field's ids.
      $ids = array_column($paragraph_field_items, 'target_id');
      // Load all paragraph objects.
      $paragraphs_objects = $paragraph_storage->loadMultiple($ids);
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    if ($paragraphs_objects) {
      foreach ($paragraphs_objects as $paragraph) {
        // Get field from the paragraph.
        $groupval    = $paragraph->get('field_class_id')->value;
        $subgroupval = $paragraph->get('field_class_sub')->value;
        $levelval    = $paragraph->get('field_class_level')->value;
        $tmpText     = '';
        // Do something with $text...
        if ($subgroupval) {
          $tmpText = $groupval . '-' . $subgroupval . '-' . $levelval;
        }
        else {
          $tmpText = $groupval . '-' . $levelval;
        }

        if (strlen($classification) > 0) {
          $classification = $classification . ', ' . $tmpText;
        }
        else {
          $classification = $tmpText;
        }
      }
      // Get equivalent flag value.
      $equivalentvalarray = $emplNode->get('field_and_equivalent')->getValue();
      $equivalentvalarray2 = $equivalentvalarray[0];
      $equivalentval = $equivalentvalarray2['value'];
      // $equivalentlbl = $emplNode->get('field_and_equivalent')->getFieldDefinition()->getLabel();
      if ($equivalentval) {
        if ($currentlangId == 'en') {
          $classification = $classification . ' and equivalent';
        }
        else {
          $classification = $classification . ' et équivalent';
        }
      }
    }

    $classification = '<div class="field--items">' . $classification . '</div>';

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
   * Retrieve the latest revision if it is a draft.
   *
   * @param int $nid
   *   Node id.
   * @param int $vid
   *   Revision id by reference.
   *
   * @return mixed
   *   Return FALSE if there is no draft revision or node otherwise the vid (revision id).
   */
  public static function latestRevision($nid, &$vid) {
    // Can be removed once we move to Drupal >= 8.6.0 , currently on 8.5.0.
    // See change record here: https://www.drupal.org/node/2942013 .
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $otherLang = 'fr';
    if ($lang == 'fr') {
      $otherLang = 'en';
    }
    $latestRevisionResult = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
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
