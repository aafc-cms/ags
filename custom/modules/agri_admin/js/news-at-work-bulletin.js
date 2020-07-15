/**
 * @file
 * Agri Admin Validation behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.agriAdminValidation = {
    attach: function (context, settings) {
      if ($('body.newsatworkbulletin').length) {
        // Only initialize in this situation.
        NewsBulletinHelper.init();
      }
    }
  };

} (jQuery, Drupal));

var NewsBulletinHelper = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var form_valid = false;

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }
    console.log('initialize agri_admin/js/news-at-work-bulletin.js');

    // Get the current UI language
    $ = jQuery;
    lang = $('html').attr('lang');

    initializeEvents();
    initialized = true;
  }
 /**
  *  Validate bulletin form.
  */
  function initializeEvents() {
    var button = jQuery('#edit-submit');
    if (jQuery(button).length) {
      jQuery(button).on('click', function(e){
        var element = $(this);
        handleClickEvent(element, e);
      });
    }
    else {
      console.log('agri_admin/js/news-at-work-bulletin.js button not found');
    }
  }

 /**
  *  Validate bulletin form.
  */
  function formRequiredFieldsValidation() {
  }

 /**
  * Function handles preview button click to perform classic validation
  * and prevent to open new tab when the form validation is false.
  */
  function handleClickEvent(element, e) {
    console.log('handlePreview click');
    formRequiredFieldsValidation();
    if (NewsBulletinHelper.form_valid == false ) {
      NewsBulletinHelper.form_valid = true;
      //now suppress mouse click for #click.
      e.preventDefault(); // Should only need this.
      return false;
    }
    if ($(element).val() != 'TEST') {
      jQuery('.node-form').removeAttr('target');
      jQuery('.node-form').attr('data-drupal-form-submit-last', '');
      jQuery('.node-form').attr('data-drupal-form-fields', '');
    }
  }

  /**
   * Expose functions and variables
   */
  return {
    init: init,
    lang: lang,
    form_valid: form_valid,
    handleClickEvent: handleClickEvent,
  }
}();

