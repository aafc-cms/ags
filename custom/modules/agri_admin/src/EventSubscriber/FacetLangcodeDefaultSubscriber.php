<?php

namespace Drupal\agri_admin\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\facets\Event\PostBuildFacet;
use Drupal\facets\Event\FacetsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Only preselect language facet, don't filter query at this level.
 */
final class FacetLangcodeDefaultSubscriber implements EventSubscriberInterface {

  protected LanguageManagerInterface $languageManager;
  protected LoggerInterface $logger;

  public function __construct(LanguageManagerInterface $language_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->languageManager = $language_manager;
    $this->logger = $logger_factory->get('agri_admin');
  }

  /**
   * Preselect current language in the facet UI if user didn't choose one.
   */
  public function onPostBuildFacet(PostBuildFacet $event): void {
    $facet = $event->getFacet();
    $params = \Drupal::request()->query->all();

    if ($facet->getUrlAlias() !== 'language') {
      return;
    }

    // Skip preselect if user explicitly chose a language facet value.
    if ($this->hasLanguageFacetParam($params)) {
      $this->logger->notice('[onPostBuildFacet] Language facet param detected, skipping default.');
      return;
    }

    // If no active item, mark current interface language as active.
    if (empty($facet->getActiveItems())) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      foreach ($facet->getResults() as $result) {
        if ($result->getRawValue() === $langcode) {
          $result->setActiveState(TRUE);
          $this->logger->notice('[onPostBuildFacet] Preselecting language: ' . $langcode);
        }
      }
    }
  }

  /**
   * Check if the GET params contain a language facet selection.
   */
  private function hasLanguageFacetParam(array $params): bool {
    foreach ($params as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $sub_value) {
          if (is_string($sub_value) && str_starts_with($sub_value, 'language:')) {
            return TRUE;
          }
        }
      } elseif (is_string($value) && str_starts_with($value, 'language:')) {
        return TRUE;
      }
    }
    return FALSE;
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

