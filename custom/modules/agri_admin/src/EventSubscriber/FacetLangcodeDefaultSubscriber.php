<?php

namespace Drupal\agri_admin\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\facets\Event\FacetsEvents;
use Drupal\facets\Event\PostBuildFacet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Default the language facet to the current language if none is selected.
 */
final class FacetLangcodeDefaultSubscriber implements EventSubscriberInterface {

  protected LanguageManagerInterface $languageManager;

  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Set the active item on the language facet if none is selected.
   */
  public function onPostBuildFacet(PostBuildFacet $event): void {
    $facet = $event->getFacet();

    // Only target the facet with machine name "language".
    if ($facet->id() === 'language' && empty($facet->getActiveItems())) {
      $current_lang = $this->languageManager->getCurrentLanguage()->getId();

      // Set the facet active items (so results are filtered).
      $facet->setActiveItems([$current_lang]);

      // Also mark the matching facet item as active so the checkbox renders checked.
      foreach ($facet->getResults() as $result) {
        if ($result->getRawValue() === $current_lang) {
          $result->setActiveState(TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      FacetsEvents::POST_BUILD_FACET => 'onPostBuildFacet',
    ];
  }

}

