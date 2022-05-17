/**
 * @file
 * WxT Overrides behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.wxtOverrides = {
    attach: function (context, settings) {
      if (context == document) {
        Error404.init();
      }
    }
  };

} (jQuery, Drupal));

var Error404 = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }
    AgrisourceFrontend.init();

    // Get the current UI language
    if (AgrisourceFrontend.lang == 'en') {
      console.log('Error 404');
      document.title = "We couldn't find that Web page (Error 404) - Agrisource";
      $('head meta[property*="title"]').remove();
      $('head').prepend('<meta property="dcterms:title" content="404 Error - Page not found">');
    }
    else {
      console.log('Erreur 404!');
      $('head meta[property*="title"]').remove();
      document.title = "Nous ne pouvons trouver cette page Web (Erreur 404) - Agrisource";
      $('head').prepend('<meta property="dcterms:title" content="Erreur 404 - Page non trouvée" lang="fr">');
    }
    $('head').prepend('<meta property="robots" content="noindex, nofollow, noarchive">');
  }

  /**
   * Expose functions and variables
   */
  return {
    init: init,
  }
}();

