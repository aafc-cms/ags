/**
 * @file
 * AgriSource Reports behaviors.
 */

(function ($, Drupal) {
  'use strict';
  /**
   * Behavior description.
   */
  Drupal.behaviors.agriReports = {
    attach: function (context, settings) {
      if (context == document) {
        console.log('Hello world from agri_reports_meta!');
      }
    }
  };
} (jQuery, Drupal));