(function (Drupal, once, drupalSettings) {
  'use strict';

  function desiredHash(lang, hasResults) {
    // For some reason they always want focus on #results.
    if (!hasResults) {
      return lang === 'fr' ? '#veuillez-reessayer' : '#please-try-again';
    }
    return lang === 'fr' ? '#resultats' : '#results';
  }

  function initClearResetButton(context) {
    const resetBtn = context.querySelector('#edit-reset-search');
    if (!resetBtn) {
      return;
    }

    once('agrisource-clear-reset-btn', resetBtn).forEach(() => {
      // Shared handler for both mousedown and click.
      const handler = function (e) {
        // Block default behavior (including submit on click) and bubbling.
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        // Only run the reset logic once per interaction: do it on click.
        if (e.type !== 'click') {
          return;
        }
        // A click event includes "enter key" or "touch" or "mouse click".

        const form = resetBtn.closest('form');
        if (!form) {
          return;
        }

        // Reset all <select> elements to first option.
        form.querySelectorAll('select').forEach(sel => {
          if (sel.options.length > 0) {
            sel.selectedIndex = 0;
            // Trigger change in case anything is listening.
            sel.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });

        // Clear and focus the search query field.
        const queryInput = form.querySelector('#edit-query');
        if (queryInput) {
          queryInput.value = '';
          // Trigger input/change so any JS watching this sees the update.
          queryInput.dispatchEvent(new Event('input', { bubbles: true }));
          queryInput.dispatchEvent(new Event('change', { bubbles: true }));
          queryInput.focus();
        }
      };

      // We:
      // - Run logic on mousedown (per your requirement).
      // - Also attach to click just to suppress the submit behavior safely.
      resetBtn.addEventListener('mousedown', handler);
      resetBtn.addEventListener('click', handler);
    });

    const tryAgain = context.querySelector('a.please-try-again, #please-try-again');
    if (!tryAgain) {
      return;
    }

    once('agrisource-please-try-again', tryAgain).forEach(() => {
      // Shared handler for both mousedown and click.
      const handler = function (e) {
        // Only run the reset logic once per interaction: do it on click.
        if (e.type !== 'click') {
          return;
        }
        // Trigger the actual reset button’s mousedown logic.
        // (This runs your reset logic exactly once.)
        resetBtn.dispatchEvent(
          new MouseEvent('mousedown', {
            bubbles: true,
            cancelable: true,
            view: window
          })
        );

        // Optional: also mimic click suppression on resetBtn.
        resetBtn.dispatchEvent(
          new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: window
          })
        );
      };
      tryAgain.addEventListener('click', handler);
    });
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

      // Init reset/clear button logic.
      initClearResetButton(context);

      // Re-run after AJAX view updates.
      document.addEventListener('drupal-ajax:complete', () => {
        normalizeLocationHash();
        normalizeLanguageSwitcherLinks();
      });
    });
  }

  Drupal.behaviors.agrisourceSearchHash = { attach };

})(Drupal, once, drupalSettings);

