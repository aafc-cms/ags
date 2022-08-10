<?php

namespace Drupal\agri_admin\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor to bypass caching on anonymous node preview.
 */
class PathProcessorAgriAdmin implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The unixtime timestamp.
   *
   * @var int
   */
  public $unixtime = NULL;

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ((isset($path) && strpos($path, 'preview') > 0)
    && (isset($path) && strpos($path, 'node') > 0)) {
      if ((isset($path) && strpos($path, 'full') > 0) || (isset($path) && strpos($path, 'teaser') > 0)) {
        if (!isset($this->unixtime)) {
          $this->unixtime = time();
        }
        $options['query']['t'] = $this->unixtime;
      }
    }
    return $path;
  }

}
