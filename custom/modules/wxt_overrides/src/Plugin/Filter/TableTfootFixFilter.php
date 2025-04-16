<?php

namespace Drupal\wxt_overrides\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Wraps orphan <tr> tags after </tbody> in a <tfoot> section.
 *
 * @Filter(
 *   id = "table_tfoot_fix_filter",
 *   title = @Translation("Fix orphan 'tr' after 'tbody' by wrapping in 'tfoot'"),
 *   description = @Translation("Wraps 'tr' elements that appear after 'tbody' with a 'tfoot' block."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 101,
 *   provider = "wxt_overrides",
 *   settings = {}
 * )
 */
class TableTfootFixFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Match each <table>...</table> block.
    $text = preg_replace_callback('#<table\b[^>]*>.*?</table>#is', function ($table_match) {
      $table = $table_match[0];

      // Match the last <tbody>...</tbody> block.
      $table = preg_replace_callback('#(<tbody\b[^>]*>.*?</tbody>)#is', function ($matches) {
        static $count = 0;
        $count++;
        // Only replace the 2nd+ tbody (e.g. totals), assuming first tbody is data.
        if ($count % 2 === 0) {
          $replaced = preg_replace('#^<tbody\b([^>]*)>#i', '<tfoot$1>', $matches[0]);
          $replaced = preg_replace('#</tbody>$#i', '</tfoot>', $replaced);
          return $replaced;
        }
        return $matches[0];
      }, $table);

      return $table;
    }, $text);

    return new FilterProcessResult($text);
  }

}
