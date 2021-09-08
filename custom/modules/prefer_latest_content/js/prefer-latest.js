/**
 * @file
 * Prefer Latest Content behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.preferLatestContent = {
    attach: function (context, settings) {
      if (jQuery('body').hasClass('user-logged-in')) {
        // Call initialize class here.
        console.log('Prefer Latest Content!');
      }
    }
  };

} (jQuery, Drupal));
