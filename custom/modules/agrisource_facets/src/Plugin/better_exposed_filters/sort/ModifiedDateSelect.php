<?php

declare(strict_types=1);

namespace Drupal\agrisource_facets\Plugin\better_exposed_filters\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort\SortWidgetBase;

/**
 * Single dropdown for Relevance / Newest / Oldest mapped to sort_by/order.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "modified_date_select",
 *   label = @Translation("Combined sort select (Relevance / Newest / Oldest)"),
 *   description = @Translation("Replaces the two sort controls with a single dropdown.")
 * )
 */
final class ModifiedDateSelect extends SortWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    // Adjust to your actual sort ids in the View.
    $relevance_key = 'search_api_relevance';
    $modified_key  = 'field_modified'; // or 'changed'.

    // Hide native sort widgets (keep values intact so existing logic can use them).
    if (isset($form['sort_by'])) {
      $form['sort_by']['#access'] = FALSE;
    }
    if (isset($form['sort_order'])) {
      $form['sort_order']['#access'] = FALSE;
    }

    // Detect current sort to set the default.
    $current_by    = $this->getExposedValue($form_state, 'sort_by');
    $current_order = strtoupper((string) ($this->getExposedValue($form_state, 'sort_order') ?? ''));

    $default = '';
    if ($current_by === $relevance_key) {
      $default = 'relevance';
    }
    elseif ($current_by === $modified_key && $current_order === 'DESC') {
      $default = 'newest';
    }
    elseif ($current_by === $modified_key && $current_order === 'ASC') {
      $default = 'oldest';
    }

    // Combined dropdown that drives sort_by/sort_order.
    $form['combined_sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => [
        'relevance' => $this->t('Relevance'),
        'newest' => $this->t('Newest'),
        'oldest' => $this->t('Oldest'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#empty_value' => '',
      '#default_value' => $default,
      '#weight' => isset($form['sort_by']['#weight']) ? $form['sort_by']['#weight'] : 0,
    ];

    // Map selection back to sort_by/sort_order on submit.
    $form['#submit'][] = function (array &$form, FormStateInterface $form_state) use ($relevance_key, $modified_key): void {
      $input  = $form_state->getUserInput();
      $choice = $input['combined_sort'] ?? '';

      if ($choice === 'relevance') {
        $form_state->setValue('sort_by', $relevance_key);
        $form_state->setValue('sort_order', 'DESC');
      }
      elseif ($choice === 'newest') {
        $form_state->setValue('sort_by', $modified_key);
        $form_state->setValue('sort_order', 'DESC');
      }
      elseif ($choice === 'oldest') {
        $form_state->setValue('sort_by', $modified_key);
        $form_state->setValue('sort_order', 'ASC');
      }
      // No selection: do nothing so your existing defaults still apply.
    };
  }

  /**
   * Read an exposed value from the form state or values.
   */
  protected function getExposedValue(FormStateInterface $form_state, string $key): ?string {
    $input = $form_state->getUserInput();
    if (isset($input[$key]) && $input[$key] !== '') {
      return (string) $input[$key];
    }
    $values = $form_state->getValues();
    if (isset($values[$key]) && $values[$key] !== '') {
      return (string) $values[$key];
    }
    return NULL;
  }

}

