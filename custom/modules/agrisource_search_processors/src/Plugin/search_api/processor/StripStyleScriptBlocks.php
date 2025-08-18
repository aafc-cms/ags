<?php

namespace Drupal\agrisource_search_processors\Plugin\search_api\processor;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;

/**
 * Removes <style>/<script> blocks (including inner text) before indexing.
 *
 * @SearchApiProcessor(
 *   id = "strip_style_script_blocks",
 *   label = @Translation("Strip &lt;style&gt; and &lt;script&gt; blocks"),
 *   description = @Translation("Removes &lt;style&gt; and &lt;script&gt; elements and their contents from indexed text."),
 *   stages = {
 *     "preprocess_index" = 50
 *   }
 * )
 */
class StripStyleScriptBlocks extends FieldsProcessorPluginBase {

  protected function fieldIsSelected(FieldInterface $field, string $field_id): bool {
    $cfg = $this->getConfiguration() + ['all_fields' => TRUE];
    if (!empty($cfg['all_fields'])) {
      return TRUE;
    }

    $enabled = $this->normalizedEnabledFields();

    // Build candidates that might be how Search API names this field.
    $candidates = [];
    $candidates[] = $field_id; // e.g. "body"
    if (method_exists($field, 'getFieldIdentifier')) {
      $candidates[] = $field->getFieldIdentifier(); // e.g. "entity:node/body"
    }
    if (method_exists($field, 'getPropertyPath')) {
      $pp = $field->getPropertyPath();
      if (is_array($pp)) {
        $candidates[] = implode(':', $pp);
        $candidates[] = implode('/', $pp);
        $last = end($pp);
        if ($last) { $candidates[] = $last; }
      }
      elseif (is_string($pp) && $pp !== '') {
        $candidates[] = $pp;
      }
    }
    if (method_exists($field, 'getDatasourceId')) {
      $ds = $field->getDatasourceId();
      foreach ($candidates as $cand) {
        $candidates[] = "$ds:$cand";
        $candidates[] = "$ds/$cand";
      }
    }

    $candidates = array_unique(array_filter($candidates, fn($v) => $v !== '' && $v !== NULL));
    foreach ($candidates as $cand) {
      if (!empty($enabled[$cand])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /** Let the UI control which fields we touch; don’t filter anything out here. */
  public static function supportsField(FieldInterface $field) {
    return TRUE;
  }

  /** The processor runs for this batch. */
  public function preprocessIndexItems(array $items) {
    $this->processItems($items);
    parent::preprocessIndexItems($items);
  }

  /** Normalize whatever the form saved into a set of enabled field ids. */
  protected function normalizedEnabledFields(): array {
    $cfg = $this->getConfiguration() + ['fields' => []];
    $raw = (array) $cfg['fields'];

    // If it's a numeric list like ['body','rendered_item'] => flip to map.
    $is_list = array_values($raw) === $raw;
    $enabled = $is_list ? array_fill_keys($raw, TRUE) : array_filter($raw);

    // E.g. ['body' => TRUE, 'rendered_item' => TRUE].
    return $enabled;
  }

  public function defaultConfiguration() {
    // Same defaults the UI expects.
    return ['all_fields' => TRUE, 'fields' => []] + parent::defaultConfiguration();
  }

  public function processItems(array &$items) {
    $debug = FALSE;
    foreach ($items as $item) {
      foreach ($item->getFields() as $field_id => $field) {

        // Respect the UI selection.
        if (!$this->fieldIsSelected($field, $field_id)) {
          continue;
        }

        // Text-ish detection and cleaning below.
        $values = $field->getValues();
        if (!$values) {
          continue;
        }
        $sample = $values[0];
        $textish = $sample instanceof \Drupal\search_api\Plugin\search_api\data_type\value\TextValue
          || $sample instanceof \Drupal\Component\Render\MarkupInterface
          || is_string($sample);
        if (!$textish) {
          continue;
        }

        $changed = FALSE;
        foreach ($values as $i => $v) {
          $is_text_value = $v instanceof \Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
          if ($is_text_value) {
            $text = $v->getText();
          }
          elseif ($v instanceof \Drupal\Component\Render\MarkupInterface) {
            $text = (string) $v;
	  }
          elseif (is_string($v)) {
            $text = $v;
          }
          else {
            continue;
          }
          if (!is_string($text) || $text === '') {
            continue;
          }

          $orig = $text;
          $text = preg_replace('~<(?:style|script)\b[^>]*>[\s\S]*?</(?:style|script)>~i', ' ', $text);
          $text = trim(preg_replace('/\s+/u', ' ', $text));

          if ($text !== $orig) {
            if ($is_text_value) {
              $v->setText($text);
              $values[$i] = $v;
            }
            else {
              $values[$i] = $text;
            }
            $changed = TRUE;
          }
        }

        if ($changed) {
          $field->setValues($values);
          if ($debug && str_contains($item->getId(), '3275')) {
            \Drupal::logger('agrisource_search_processors')
              ->notice('[strip] cleaned @f on @id', ['@f' => $field_id, '@id' => $item->getId()]);
          }
        }
      }
    }
  }

}

