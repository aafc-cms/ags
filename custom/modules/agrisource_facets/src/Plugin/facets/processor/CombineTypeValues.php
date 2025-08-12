<?php

declare(strict_types=1);

namespace Drupal\agrisource_facets\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
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
final class CombineTypeValues extends ProcessorPluginBase implements BuildProcessorInterface {

  public function defaultConfiguration(): array {
    return [
      'facet_id' => 'type',
      'virtual_value' => 'agrisource',
      'virtual_label' => 'Pages',
      'source_values' => ['page', 'landing_page'],
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
    return $form;
  }

  public function submitConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $this->setConfiguration([
      'facet_id' => (string) $form_state->getValue('facet_id'),
      'virtual_value' => (string) $form_state->getValue('virtual_value'),
      'virtual_label' => (string) $form_state->getValue('virtual_label'),
      'source_values' => array_values(array_filter(array_map('trim', explode("\n", (string) $form_state->getValue('source_values'))))),
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

}

