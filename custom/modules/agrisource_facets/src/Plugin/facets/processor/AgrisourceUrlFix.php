<?php

declare(strict_types=1);

namespace Drupal\agrisource_facets\Plugin\facets\processor;

use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Forces the Agrisource link to be a "remove" URL if it's already selected.
 *
 * @FacetsProcessor(
 *   id = "agrisource_url_fix",
 *   label = @Translation("Agrisource URL fix"),
 *   description = @Translation("When ?f[]=type:agrisource is present, force the Agrisource result URL to remove that param (and dedupe)."),
 *   stages = {
 *     "build" = 20
 *   }
 * )
 */
final class AgrisourceUrlFix extends ProcessorPluginBase implements BuildProcessorInterface {

  public function build(FacetInterface $facet, array $results) {
    if ($facet->id() !== 'type') {
      return $results;
    }

    $request = \Drupal::requestStack()->getCurrentRequest();
    $query   = $request->query->all();
    $flat    = [];
    $this->flatten($query['f'] ?? [], $flat);

    $selected = in_array('type:agrisource', $flat, true);

    if (!$selected) {
      return $results; // Nothing to fix; default URL is fine (add-link).
    }

    // Build a query without ANY "type:agrisource" occurrences (dedupe removal).
    $cleanF = $this->filterOut($query['f'] ?? [], 'type:agrisource');
    $newQuery = $query;
    if ($cleanF) {
      $newQuery['f'] = $cleanF;
    }
    else {
      unset($newQuery['f']);
    }

    $removeUrl = Url::fromUserInput($request->getPathInfo(), ['query' => $newQuery]);

    foreach ($results as $r) {
      if ($r->getRawValue() === 'agrisource' && method_exists($r, 'setUrl')) {
        $r->setUrl($removeUrl);
      }
    }

    return $results;
  }

  private function flatten($value, array &$out): void {
    if (is_array($value)) {
      foreach ($value as $v) {
        $this->flatten($v, $out);
      }
    }
    elseif ($value !== null) {
      $out[] = (string) $value;
    }
  }

  private function filterOut($value, string $needle) {
    if (is_array($value)) {
      $kept = [];
      foreach ($value as $k => $v) {
        $filtered = $this->filterOut($v, $needle);
        if ($filtered !== null && $filtered !== [] && $filtered !== '') {
          $kept[$k] = $filtered;
        }
      }
      return $kept;
    }
    // leaf
    return ((string) $value === $needle) ? null : $value;
  }
}

