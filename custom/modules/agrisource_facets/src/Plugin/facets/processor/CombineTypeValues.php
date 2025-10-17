<?php

declare(strict_types=1);

namespace Drupal\agrisource_facets\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Result\Result;


/**
 * Combines 'page' + 'landing_page' into one virtual option "Pages".
 *
 * @FacetsProcessor(
 *   id = "combine_type_values",
 *   label = @Translation("Combine values into a virtual option"),
 *   description = @Translation("Expose one UI option that maps to multiple raw values."),
 *   stages = {
 *     "build" = 12
 *   }
 * )
 */
final class CombineTypeValues extends ProcessorPluginBase implements BuildProcessorInterface, PreQueryProcessorInterface {

  public function defaultConfiguration(): array {
    return [
      'facet_id' => 'type',
      'virtual_value' => 'agrisource',
      'virtual_label' => 'Pages',
      'source_values' => ['page', 'landing_page'],
      // NEW: let us read the exposed filter param directly from the request.
      'exposed_param' => '', // leave empty to auto-detect
    ];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $conf = $this->getConfiguration();
    $form['facet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facet machine name'),
      '#default_value' => $conf['facet_id'] ?: $facet->id(),
      '#required' => TRUE,
    ];
    $form['virtual_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Virtual raw value'),
      '#default_value' => $conf['virtual_value'],
      '#required' => TRUE,
    ];
    $form['virtual_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Virtual label'),
      '#default_value' => $conf['virtual_label'],
      '#required' => TRUE,
    ];
    $form['source_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Raw values to combine (one per line)'),
      '#default_value' => implode("\n", $conf['source_values']),
    ];
    $form['exposed_param'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exposed filter parameter name'),
      '#default_value' => (string) $conf['exposed_param'],
      '#description' => $this->t('Usually the Views exposed filter key for this facet (e.g., "type"). Leave empty to auto-detect from the facet; set explicitly if BEF/custom forms rename it.'),
    ];

    return $form;
  }

  public function submitConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $this->setConfiguration([
      'facet_id' => (string) $form_state->getValue('facet_id'),
      'virtual_value' => (string) $form_state->getValue('virtual_value'),
      'virtual_label' => (string) $form_state->getValue('virtual_label'),
      'source_values' => array_values(array_filter(array_map('trim', explode("\n", (string) $form_state->getValue('source_values'))))),
      'exposed_param' => trim((string) $form_state->getValue('exposed_param')),
    ]);
  }

  public function build(FacetInterface $facet, array $results) {
    $c = $this->getConfiguration();
    if ($facet->id() !== $c['facet_id']) {
      return $results;
    }

    $items   = $facet->getActiveItems() ?: [];
    $filters = method_exists($facet, 'getActiveFilters') ? ($facet->getActiveFilters() ?: []) : [];

    $hasVirtual   = in_array($c['virtual_value'], $items, TRUE) || in_array($c['virtual_value'], $filters, TRUE);
    $hasAnySource = (bool) array_intersect($items, $c['source_values']) || (bool) array_intersect($filters, $c['source_values']);

    // --- Normalize ACTIVE STATE for the URL builder/UI ---
    // If either the virtual or any of its sources are active, collapse to the virtual token.
    if ($hasVirtual || $hasAnySource) {
      $newItems = array_values(array_unique(array_merge(
        array_diff($items, $c['source_values']),
        [$c['virtual_value']]
      )));
      if ($newItems !== $items) {
        $facet->setActiveItems($newItems);
        $items = $newItems;
      }

      if (method_exists($facet, 'setActiveFilters')) {
        $newFilters = array_values(array_unique(array_merge(
          array_diff($filters, $c['source_values']),
          [$c['virtual_value']]
        )));
        if ($newFilters !== $filters) {
          $facet->setActiveFilters($newFilters);
          $filters = $newFilters;
        }
      }
    }
    else {
      // Ensure virtual isn't falsely marked active when nothing is selected.
      if (in_array($c['virtual_value'], $items, TRUE)) {
        $facet->setActiveItems(array_values(array_diff($items, [$c['virtual_value']])));
      }
      if ($filters && method_exists($facet, 'setActiveFilters') && in_array($c['virtual_value'], $filters, TRUE)) {
        $facet->setActiveFilters(array_values(array_diff($filters, [$c['virtual_value']])));
      }
    }
    // -----------------------------------------------------

    // Sum counts for the sources, hide them, and add the single virtual result.
    $sum = 0;
    $kept = [];
    foreach ($results as $r) {
      if (in_array($r->getRawValue(), $c['source_values'], TRUE)) {
        $sum += $r->getCount();
        continue; // hide 'page' / 'landing_page'
      }
      $kept[] = $r;
    }

    if ($sum > 0) {
      $kept[] = new \Drupal\facets\Result\Result(
        $facet,
        $c['virtual_value'],
        $c['virtual_label'],
        $sum
      );
    }

    return $kept;
  }

  public function preQuery(FacetInterface $facet) {
    $c = $this->getConfiguration();
    if ($facet->id() !== $c['facet_id']) {
      return;
    }

    $virtual  = (string) $c['virtual_value'];
    $sources  = (array) $c['source_values'];
    $param    = $this->resolveParamName($facet, (string) ($c['exposed_param'] ?? ''));

    // 1) Try the facet’s active selections first (when available).
    $items   = $facet->getActiveItems() ?: [];
    $filters = method_exists($facet, 'getActiveFilters') ? ($facet->getActiveFilters() ?: []) : [];

    $virtual_active = in_array($virtual, $items, TRUE) || in_array($virtual, $filters, TRUE);

    // 2) Fallback: read the selection directly from the current request.
    if (!$virtual_active) {
      $request = \Drupal::requestStack()->getCurrentRequest();
      $raw = $request->query->all()[$param] ?? null;

      // $raw can be scalar or array (BEF multi-select). Normalize to array.
      $selected_values = is_array($raw) ? array_map('strval', $raw) : (isset($raw) ? [(string) $raw] : []);

      // Consider "active" if param equals the virtual token.
      $virtual_active = in_array($virtual, $selected_values, TRUE);
      // Also treat it as "active" if you already selected one of the real sources.
      $any_source_active = (bool) array_intersect($sources, $selected_values);
    } else {
      $any_source_active = (bool) array_intersect($sources, $items) || (bool) array_intersect($sources, $filters);
    }

    // If neither the virtual nor its sources are selected, nothing to do.
    if (!$virtual_active && !$any_source_active) {
      return;
    }

    // Replace the virtual token with its source values for the facet’s own state.
    // (If a source is already selected, this just normalizes the set.)
    $new_items = array_values(array_unique(array_merge(array_diff($items, [$virtual]), $sources)));
    $facet->setActiveItems($new_items);

    if (method_exists($facet, 'setActiveFilters')) {
      $new_filters = array_values(array_unique(array_merge(array_diff($filters, [$virtual]), $sources)));
      $facet->setActiveFilters($new_filters);
    }
  }

  private function resolveParamName(FacetInterface $facet, string $configured): string {
    if ($configured !== '') {
      return $configured;
    }
    // Try common facet APIs to guess the exposed key.
    // Different facet sources expose different helpers; we defensively probe.
    if (method_exists($facet, 'getUrlAlias') && ($alias = (string) $facet->getUrlAlias())) {
      return $alias; // often equals the exposed param
    }
    if (method_exists($facet, 'getFieldIdentifier') && ($id = (string) $facet->getFieldIdentifier())) {
      return $id; // for most Search API facets this is the filter key
    }
    // Fall back to the common case you’re using.
    return 'type';
  }
}

