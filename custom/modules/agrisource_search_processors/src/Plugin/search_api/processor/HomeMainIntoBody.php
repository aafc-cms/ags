<?php

namespace Drupal\agrisource_search_processors\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase; // <-- your working base class
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @SearchApiProcessor(
 *   id = "home_main_into_body",
 *   label = @Translation("Homepage <main> into Body"),
 *   description = @Translation("For node/20, renders the full page, extracts <main>, and replaces the Body field values before indexing."),
 *   stages = {
 *     "add_field_values" = -50
 *   }
 * )
 */
final class HomeMainIntoBody extends ProcessorPluginBase {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly HttpKernelInterface $httpKernel
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_kernel')
    );
  }

  /**
   * Runs early in "add_field_values": we can overwrite Body values here.
   */
  public function addFieldValues(ItemInterface $item): void {
    $entity = $item->getOriginalObject()->getValue();
    if (!$entity || $entity->getEntityTypeId() !== 'node' || (int) $entity->id() !== 20) {
      return;
    }

    // Render full page as SUB_REQUEST so regions/blocks are included.
    $current = \Drupal::request();
    $host = $current?->getHost() ?? 'localhost';
    $scheme = $current?->getScheme() ?? 'http';

    // If your real front path isn't /node/20, you can pull it from config:
    // $path = \Drupal::config('system.site')->get('page.front') ?: '/';
    $request = Request::create('/node/20', 'GET', [], [], [], [
      'HTTP_HOST' => $host,
      'HTTP_X_FORWARDED_PROTO' => $scheme,
      'HTTPS' => $scheme === 'https' ? 'on' : 'off',
    ]);
    $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);
    $html = (string) $response->getContent();
    if ($html === '') {
      return;
    }

    // Extract <main> text, collapse whitespace.
    $dom = Html::load($html);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//main');
    if (!$nodes || !$nodes->length) {
      return;
    }
    $text = '';
    foreach ($nodes as $n) {
      $text .= ' ' . trim($n->textContent ?? '');
    }
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '') {
      return;
    }

    // Overwrite any field mapped from Body (body/value or field_body, etc.).
    foreach ($item->getFields() as $field) {
      $path = (string) $field->getPropertyPath();
      if (preg_match('/(^|:)body(\/|$)/', $path)) {
        $field->setValues([$text]);
      }
    }
  }

}

