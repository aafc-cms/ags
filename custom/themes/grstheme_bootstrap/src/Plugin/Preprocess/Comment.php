<?php

namespace Drupal\grstheme_bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @ingroup plugins_form
 *
 * @BootstrapPreprocess("comment")
 */
class Comment extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    $variables['author'] = \Drupal::service('renderer')->render($elements);
    if (empty($variables['author']) && $variables['comment']->getOwner()->id() === 0) {
      if ($variables['comment']->hasField('field_my_name')) {
        $user = $variables['comment']->get('field_my_name');
	$variables['author'] = $user->value;
      }
    }
    if (empty($variables['author']) && $variables['comment']->getOwner()->isAuthenticated()) {
      $first_name = $variables['comment']->getOwner()->get('field_first_name')->getString();
      $last_name = $variables['comment']->getOwner()->get('field_last_name')->getString();
      if (!empty($first_name) && !empty($last_name)) {
        $variables['author'] = $first_name . ' ' . $last_name;
      }
    }
    if (empty($variables['author'])) {
      $variables['author'] = $variables['comment']->getAuthorName();
    }

    // Getting the node creation time stamp from the comment object.
    $date = $variables['comment']->getCreatedTime();

    // Adjust submitted display.
    $variables['created'] = \Drupal::service('date.formatter')->format($date, 'wxt_standard');
    $variables['submitted'] = $this->t('@username - <span class="comments-ago">@datetime </span>', [
      '@username' => $variables['author'],
      '@datetime' => $variables['created'],
    ]);
  }

}
