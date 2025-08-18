<?php

namespace Drupal\z_search_agrisource\Plugin\search_api\processor;

use Drupal\Component\Render\MarkupInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

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

  /** Let the UI control which fields we touch; don’t filter anything out here. */
  public static function supportsField(FieldInterface $field) {
    return TRUE;
  }

  /** (Optional) prove the processor runs for this batch. */
  public function preprocessIndexItems(array $items) {
    $this->processItems($items);
    parent::preprocessIndexItems($items);
  }

  public function processItems(array &$items) {

    foreach ($items as $item) {
      foreach ($item->getFields() as $field_id => $field) {
        $type = $field->getType();
        if ($type !== 'text' && $type !== 'string') {
          continue;
        }

        $values = $field->getValues();
        $changed = FALSE;
        foreach ($values as $i => $v) {
          if ($v instanceof \Drupal\Component\Render\MarkupInterface) {
            $v = (string) $v;
          }
          if (!is_string($v) || $v === '') continue;

          $orig = $v;
          $v = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $v);
          $v = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $v);
          $v = preg_replace('/(?:^|\s)[.#@]?[a-zA-Z0-9_-][^{]{0,400}\{[^}]{0,2000}\}/m', ' ', $v);
          $v = trim(preg_replace('/\s+/u', ' ', $v));

          // Add sentinel only for our node/field to test.
          if ($item->getId() === 'entity:node/3275:en' && $field_id === 'body') {
            $v = '[STRIPPED✔] ' . $v;
          }

          if ($v !== $orig) { $values[$i] = $v; $changed = TRUE; }
        }
        if ($changed) {
          $field->setValues($values);
          $changed = FALSE;
	  if (str_contains($item->getId(), '3275')) {
            \Drupal::logger('z_search_agrisource')->notice('[strip] batch cleaned @f on @id', ['@f' => $field_id, '@id' => $item->getId()]);
	  }
        }
      }
    }
  }

}

