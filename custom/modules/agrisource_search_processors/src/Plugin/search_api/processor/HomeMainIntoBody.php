<?php

namespace Drupal\agrisource_search_processors\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
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

  /**
   * Constructs the processor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly HttpKernelInterface $httpKernel,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_kernel'),
      $container->get('config.factory'),
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
    $request = $this->createSubRequest('/node/20');
    \Drupal::logger('agrisource_search_processors')->notice(
      "SUB_REQUEST DEBUG:\nURL=@url\nPath=@path\nServer=@server\nHeaders=@headers",
       [
        '@url'     => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
        '@path'    => $request->getPathInfo(),
        '@server'  => json_encode($request->server->all(), JSON_PRETTY_PRINT),
        '@headers' => json_encode($request->headers->all(), JSON_PRETTY_PRINT),
      ]
    );
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

  /**
   * Build a sub-request to the given path, using linkchecker overrides if set.
   */
  private function createSubRequest(string $path): Request {
    // Defaults from current request.
    $current = \Drupal::request();
    $scheme = $current?->getScheme() ?? 'http';
    $host = $current?->getHost() ?? 'localhost';
    $port = $current?->getPort() ?: null;

    $is_cloud = TRUE;

    if (stripos($host, 'pro') !== FALSE || stripos($host, 'ddev') !== FALSE) {
      $is_cloud = FALSE;
    }
    // Try to reuse linkchecker.settings if present.
    $config = $this->configFactory->get('linkchecker.settings');
    if ($config && $is_cloud) {
      $override_host_url = $config->get('localhost_url');
      $override_host_port = $config->get('localhost_port_number');

      if (!empty($override_host_url)) {
        // localhost_url might be "http://web:8080" or just "web".
        $parsed = @parse_url($override_host_url);
        if ($parsed !== false) {
          if (!empty($parsed['scheme'])) {
            $scheme = $parsed['scheme'];
          }
          if (!empty($parsed['host'])) {
            $host = $parsed['host'];
          }
          elseif (!empty($parsed['path'])) {
            // If someone put "web" without scheme.
            $host = $parsed['path'];
          }
          if (!empty($parsed['port'])) {
            $port = $parsed['port'];
          }
        }
        else {
          // Fallback: treat whole string as host.
          $host = $override_host_url;
        }
      }

      if (!empty($override_host_port)) {
        $port = $override_host_port;
      }
    }

    // Build SERVER array for the sub-request.
    $server = [
      'HTTP_HOST' => $port && !in_array((int) $port, [80, 443], TRUE) ? $host . ':' . $port : $host,
      'SERVER_NAME' => $host,
      'SERVER_PORT' => $port ?: ($scheme === 'https' ? 443 : 80),
      'HTTP_X_FORWARDED_PROTO' => $scheme,
      'HTTPS' => $scheme === 'https' ? 'on' : 'off',
    ];

    return Request::create($path, 'GET', [], [], [], $server);
  }

}

