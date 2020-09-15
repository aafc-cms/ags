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
      if ($('.block-formblock').length && $('#edit-preview').length) {
        // Only initialize when the formblock becomes available.
        AgriHelper.init();
      }
      else if ($('body').hasClass('node-add') &&
        $('#edit-preview').length ) {
        AgriHelper.init();
      }
    }
  };

} (jQuery, Drupal));

var AgriHelper = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var form_required_valid = true; // Flag to indicate that the validation is for the Empl (EO) forms
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var page_type = 'content';

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }
    console.log('initialize agri_admin/js/agri-empl-submit.js');

    // Get the current UI language
    $ = jQuery;
    lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('nodeaddempl')) {
      AgriHelper.page_type = 'add-empl-special';
    }
    if ($('body').hasClass('nodeaddempl')) {
      AgriHelper.page_type = 'add-empl-special';
    }
    //Determine the page type
    if ($('body').hasClass('employment-opportunity-request-form-page')) {
      AgriHelper.page_type = 'add-empl-special';
    }
    else if ($('body').hasClass('news-article-form-page')) {
      AgriHelper.page_type = 'add-news-special';
    }

    console.log(AgriHelper.page_type);

    initialized = true;
  }

  function addSubmitClickHandler() {
  }


  /*
    validate empl (EO) form.
  */
  function formRequiredFieldsValidation() {
    $('select[required="required"]').each(function(index, element) {
      if ($(element).hasClass('form-select')) {
        if ($(element).val() == '_none') {
          AgriHelper.form_required_valid = false;
          return AgriHelper.form_required_valid;
        }
      }
    });
  }

/**
  * Function handles preview button click to perform classic validation
  * and prevent to open new tab when the form validation is false.
  */
  function handlePreviewClickEvent(element, e) {
    console.log('handlePreview click');
    formRequiredFieldsValidation();
  }

/**
  * Function handles button click event to perform classic validation
  * and prevent to open new tab when the form validation is false.
  */
  function handleSubmitClickEvent(element, e) {
    console.log('handleSubmit click');
    formRequiredFieldsValidation();
  }

  /**
   * Expose functions and variables
   */
  return {
    init: init,
    lang: lang,
    form_required_valid: form_required_valid,
    handleSubmitClickEvent: handleSubmitClickEvent,
    handlePreviewClickEvent: handlePreviewClickEvent,
    page_type: page_type,
  }
}();

