<?php

namespace Drupal\wxt_overrides\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Fixes split <span> + <a> tags caused by CKEditor5.
 *
 * @Filter(
 *   id = "span_link_fix_filter",
 *   title = @Translation("Fix CKEditor5 split span/link issue"),
 *   description = @Translation("Merges text/link into one span to fix GC Steps."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 100,
 *   provider = "wxt_overrides",
 *   settings = {}
 * )
 */
class SpanLinkFixFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $pattern = '#<span class="([^"]*\blist-group-item\b[^"]*)">([^<]+)</span>\s*<a([^>]+)><span class="([^"]*\blist-group-item\b[^"]*)">([^<]+)</span></a>#i';

    $text = preg_replace_callback($pattern, function ($matches) {
      $span1_classes = $matches[1];   // All classes from the first span
      $before_text = $matches[2];     // Text from the first span
      $link_attrs = $matches[3];      // Attributes on the <a> tag
      $span2_classes = $matches[4];   // All classes from the second span
      $link_text = $matches[5];       // Text inside the second span

      // Rebuild the merged output using the original classes from the first span
      return '<span class="' . $span1_classes . '">' . $before_text . '<a' . $link_attrs . '>' . $link_text . '</a></span>';
    }, $text);

    return new FilterProcessResult($text);
  }

}
