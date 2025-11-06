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

    $bundle = $entity->bundle();
    $max_levels = \in_array($bundle, ['news', 'empl'], TRUE) ? 1 : 2;

    $token = \Drupal::token();
    $alias_manager = \Drupal::service('path_alias.manager');

    $explode_alias = static function (string $alias): array {
      return array_values(array_filter(explode('/', trim($alias, '/')), 'strlen'));
    };

    $prettify = static function (string $segment, string $lang): string {
      $clean = rawurldecode($segment);
      $clean = str_replace('-', ' ', $clean);
      $clean = Html::decodeEntities($clean);
      $clean = Unicode::ucfirst($clean);

      // Localize browse root.
      if (\strcasecmp($clean, 'Browse') === 0 || \strcasecmp($clean, 'Parcourir') === 0) {
        return $lang === 'fr' ? 'Parcourir' : 'Browse';
      }

      // Project-specific fixes.
      if ($lang === 'en') {
        $clean = str_replace('newswork', 'news@work', $clean);
        if ($clean === 'Newswork') {
          $clean = 'news@work';
        }
      }
      else {
        if ($clean === 'Possibilites demploi a linterne') {
          $clean = "Possibilités d'emploi à l'interne";
        }
        $clean = str_replace('nouvelleslouvrage', "nouvelles@l'ouvrage", $clean);
        if ($clean === 'Nouvelleslouvrage') {
          $clean = "nouvelles@l'ouvrage";
        }
      }

      return $clean;
    };

    $parent_from_menu = static function (NodeInterface $node, string $lang) use ($token): string {
      $title = trim((string) $token->replace('[node:menu-link:parent:title]', ['node' => $node], ['langcode' => $lang]));
      if ($title !== '' && $title !== '[node:menu-link:parent:title]') {
        if ($lang === 'en' && strcasecmp($title, 'Parcourir') === 0) return 'Browse';
        if ($lang === 'fr' && strcasecmp($title, 'Browse') === 0) return 'Parcourir';
        return $title;
      }
      return '';
    };

    $resolve_alias = static function (NodeInterface $node, string $lang) use ($alias_manager): ?string {
      $src = '/node/' . $node->id();
      foreach ([$lang, LanguageInterface::LANGCODE_NOT_APPLICABLE, LanguageInterface::LANGCODE_NOT_SPECIFIED, $lang === 'fr' ? 'en' : 'fr'] as $try) {
        $alias = $alias_manager->getAliasByPath($src, $try);
        if (is_string($alias) && $alias !== $src) {
          return $alias;
        }
      }
      return NULL;
    };

    $build_lang_value = function (string $lang) use (
      $entity,
      $max_levels,
      $parent_from_menu,
      $resolve_alias,
      $explode_alias,
      $prettify
    ): string {
      $crumbs = [];

      // Preferred "parent" comes from menu if available; otherwise alias second-last.
      $menu_parent = $parent_from_menu($entity, $lang);
      $alias = $resolve_alias($entity, $lang);
      $parts = $alias ? $explode_alias($alias) : [];

      // Alias parent (second-last) and grandparent (third-last) segments.
      $alias_parent = '';
      $alias_grandparent = '';
      if (!empty($parts)) {
        $n = count($parts);
        // parent = second-last if present; else last if only one segment.
        if ($n >= 2) {
          $alias_parent = $parts[$n - 2];
        }
        elseif ($n === 1) {
          $alias_parent = $parts[0];
        }
        // grandparent = third-last if present.
        if ($n >= 3) {
          $alias_grandparent = $parts[$n - 3];
        }
      }

      // Determine the "parent" crumb.
      $parent_label = $menu_parent !== '' ? $menu_parent : ($alias_parent !== '' ? $alias_parent : '');
      if ($parent_label !== '') {
        $crumbs[] = $prettify($parent_label, $lang);
      }

      // If we are allowed two levels, try to add the "grandparent".
      if ($max_levels === 2) {
        // If menu gave us a parent, grandparent comes from alias third-last (if any).
        if ($alias_grandparent !== '') {
          // Avoid duplicate browse/parcourir if already present.
          $gp = $prettify($alias_grandparent, $lang);
          if (empty($crumbs) || strcasecmp(end($crumbs), $gp) !== 0) {
            array_unshift($crumbs, $gp); // grandparent before parent
          }
        }
      }

      // Enforce maximum levels and normalize order: grandparent > parent.
      $crumbs = array_values(array_filter($crumbs, 'strlen'));
      if (count($crumbs) > $max_levels) {
        $crumbs = array_slice($crumbs, -$max_levels);
      }

      // Join with " > " as requested.
      return $crumbs ? implode(' > ', $crumbs) : '';
    };

    $en = $build_lang_value('en');
    $fr = $build_lang_value('fr');

    $helper = $this->getFieldsHelper();

    if ($en !== '') {
      foreach ($helper->filterForPropertyPath($item->getFields(), NULL, 'agrisource_parent_menu_title') as $field) {
        $field->setValues([$en]);
      }
    }
    if ($fr !== '') {
      foreach ($helper->filterForPropertyPath($item->getFields(), NULL, 'agrisource_parent_menu_title_fr') as $field) {
        $field->setValues([$fr]);
      }
    }
  }


}

