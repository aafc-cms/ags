(function (Drupal, once, drupalSettings) {
  'use strict';

  function desiredHash(lang, hasResults) {
    if (!hasResults) {
      return '#edit-query';
    }
    return lang === 'fr' ? '#resultats' : '#results';
  }

  function normalizeLocationHash() {
    const lang = (document.documentElement.getAttribute('lang') || 'en').toLowerCase().slice(0, 2);
    const hasResults = !!(drupalSettings.agrisourceSearch && drupalSettings.agrisourceSearch.hasResults);
    const want = desiredHash(lang, hasResults);

    if (location.hash !== want) {
      const newUrl = location.pathname + location.search + want;
      history.replaceState(null, '', newUrl);
    }
  }

  function normalizeLanguageSwitcherLinks() {
    const hasResults = !!(drupalSettings.agrisourceSearch && drupalSettings.agrisourceSearch.hasResults);

    document.querySelectorAll('.block-language a[hreflang], .language-switcher a[hreflang]')
      .forEach(a => {
        const targetLang = a.getAttribute('hreflang').toLowerCase().slice(0, 2);
        const want = desiredHash(targetLang, hasResults);
        const url = new URL(a.href, location.origin);
        url.hash = want;
        a.href = url.toString();
      });
  }

  function attach(context) {
    once('agrisource-search-hash', 'html', context).forEach(() => {
      normalizeLocationHash();
      normalizeLanguageSwitcherLinks();

      // Re-run after AJAX view updates.
      document.addEventListener('drupal-ajax:complete', () => {
        normalizeLocationHash();
        normalizeLanguageSwitcherLinks();
      });
    });
  }

  Drupal.behaviors.agrisourceSearchHash = { attach };

})(Drupal, once, drupalSettings);

