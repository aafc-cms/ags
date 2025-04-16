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
    // Match closing </tbody> followed by one or more <tr> elements not already wrapped.
    $pattern = '#</tbody>\s*((?:<tr\b.*?</tr>\s*)+)(?!\s*</tfoot>)#is';

    $text = preg_replace_callback($pattern, function ($matches) {
      return '</tbody><tfoot>' . $matches[1] . '</tfoot>';
    }, $text);

    return new FilterProcessResult($text);
  }

}
