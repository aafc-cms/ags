<?php

declare(strict_types=1);

namespace Drupal\agrisource_search_processors\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Plugin\search_api\processor\Property\CustomValueProperty;

/**
 * Adds parent menu title (EN/FR) from menu parent or second-last alias segment.
 *
 * @SearchApiProcessor(
 *   id = "agrisource_parent_menu_title",
 *   label = @Translation("Agrisource: Parent menu title"),
 *   description = @Translation("Adds parent menu title (EN/FR) from menu parent or second-last alias segment."),
 *   stages = {
 *     "add_properties" = 0,
 *     "add_field_values" = 0
 *   }
 * )
 */
final class ParentMenuTitle extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    if ($datasource) {
      return [];
    }

    // Reuse CustomValueProperty as a generic string property container.
    $defs = [];

    $defs['agrisource_parent_menu_title'] = new CustomValueProperty([
      'label' => $this->t('Agrisource parent menu title (EN)'),
      'description' => $this->t('English parent menu title derived from menu parent or alias.'),
      'type' => 'string',
      'processor_id' => $this->getPluginId(),
    ]);

    $defs['agrisource_parent_menu_title_fr'] = new CustomValueProperty([
      'label' => $this->t('Agrisource parent menu title (FR)'),
      'description' => $this->t('French parent menu title derived from menu parent or alias.'),
      'type' => 'string',
      'processor_id' => $this->getPluginId(),
    ]);

    return $defs;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item): void {
    $entity = $item->getOriginalObject()->getValue();
    if (!$entity instanceof NodeInterface) {
      return;
    }

    $token = \Drupal::token();
    $alias_manager = \Drupal::service('path_alias.manager');

    $pick_second_last = static function (string $alias): ?string {
      $parts = array_values(array_filter(explode('/', trim($alias, '/')), 'strlen'));
      $n = count($parts);
      return $n >= 2 ? $parts[$n - 2] : ($n === 1 ? $parts[0] : NULL);
    };

    $prettify = static function (string $segment): string {
      $clean = rawurldecode($segment);
      $clean = str_replace('-', ' ', $clean);
      $clean = Html::decodeEntities($clean);
      return Unicode::ucfirst($clean);
    };

    $resolve = static function (NodeInterface $node, string $lang) use ($token, $alias_manager, $pick_second_last, $prettify): string {
      // 1) Menu parent title in requested lang; ignore unresolved literal token.
      $title = trim((string) $token->replace('[node:menu-link:parent:title]', ['node' => $node], ['langcode' => $lang]));
      if ($title !== '' && $title !== '[node:menu-link:parent:title]') {
        if ($lang === 'en' && strcasecmp($title, 'Parcourir') === 0) return 'Browse';
        if ($lang === 'fr' && strcasecmp($title, 'Browse') === 0) return 'Parcourir';
        return $title;
      }

      // 2) Alias fallback: requested → neutral → not_specified → other lang.
      $src = '/node/' . $node->id();
      foreach ([$lang, LanguageInterface::LANGCODE_NOT_APPLICABLE, LanguageInterface::LANGCODE_NOT_SPECIFIED, $lang === 'fr' ? 'en' : 'fr'] as $try) {
        $alias = $alias_manager->getAliasByPath($src, $try);
        if (is_string($alias) && $alias !== $src) {
          $seg = $pick_second_last($alias);
          if ($seg) {
            if ($seg === 'browse' || $seg === 'parcourir') {
              return $lang === 'fr' ? 'Parcourir' : 'Browse';
            }
            return $prettify($seg);
          }
        }
      }
      return '';
    };

    $en = $resolve($entity, 'en');
    $fr = $resolve($entity, 'fr');

    $helper = $this->getFieldsHelper();

    if ($en !== '') {
      foreach ($helper->filterForPropertyPath($item->getFields(), NULL, 'agrisource_parent_menu_title') as $field) {
        $field->setValues([$en]);
      }
    }
    if ($fr !== '') {
      foreach ($helper->filterForPropertyPath($item->getFields(), NULL, 'agrisource_parent_menu_title_fr') as $field) {
        if ($fr == 'Possibilites demploi a linterne') {
          $fr = "Possibilités d'emploi à l'interne";
        }
        if ($en == 'Newswork') {
          $en = "news@work";
        }
        if ($fr == 'Nouvelleslouvrage') {
          $fr = "nouvelles@l'ouvrage";
        }
        $field->setValues([$fr]);
      }
    }
  }

}

