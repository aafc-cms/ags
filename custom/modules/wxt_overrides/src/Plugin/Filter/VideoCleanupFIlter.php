<?php

namespace Drupal\wxt_overrides\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Removes <p> tags wrapping around <video> elements caused by CKEditor5.
 *
 * @Filter(
 *   id = "p_video_remove_filter",
 *   title = @Translation("Remove 'p' tag around 'video' elements"),
 *   description = @Translation("Removes the 'p' tag wrapping 'video' tags."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 100,
 *   provider = "wxt_overrides",
 *   settings = {}
 * )
 */
class VideoCleanupFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Pattern to match <p> wrapping <video> elements.
    $pattern = '#<p>\s*(<video[^>]*>.*?</video>)\s*</p>#is';

    // Use preg_replace_callback to remove the <p> and keep the <video> content.
    $text = preg_replace_callback($pattern, function ($matches) {
      // $matches[1] contains the <video> tag and its content, we return it directly.
      return $matches[1];
    }, $text);

    // Return the transformed text.
    return new FilterProcessResult($text);
  }

}
