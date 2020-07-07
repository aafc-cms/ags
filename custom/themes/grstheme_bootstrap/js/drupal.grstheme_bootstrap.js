/**
 * @file
 * Drupal WxT Bootstrap object.
 */

/**
 * All Drupal Agrisource grstheme Bootstrap JavaScript APIs are contained in this namespace.
 *
 * @namespace
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.grstheme_bootstrap = {
    attach: function (context, settings) {
      if (context == document) {
        jQuery('body.i18n-en a.french-only').each(function(index, element){
          // For Keyboard navigation fix in English.
          jQuery(element).closest('li').remove();
        });
        jQuery('body.i18n-fr a.english-only').each(function(index, element){
          // For Keyboard navigation fix in French.
          jQuery(element).closest('li').remove();
        });
      }
    }
  }
  Drupal.grstheme_bootstrap = {
    settings: drupalSettings.grstheme_bootstrap || {},
  };

  /**
   * Returns the version of WxT being used.
   *
   * @return {string}
   *   The version of WxT being used.
   */
  Drupal.grstheme_bootstrap.version = 'Agrisource v1.0';

  console.log(Drupal.grstheme_bootstrap.version);

})(window.jQuery, window.Drupal, window.drupalSettings);
