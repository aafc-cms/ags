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
      AgriHelper.init();
      console.log('initialized agri_admin/js/agri-empl-or-news-submit.js');
    }
  };

} (jQuery, Drupal));

var AgriHelper = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var form_required_valid = true; // Flag to indicate that the validation is for the News and EO forms
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var page_type = 'content';

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }

    // Get the current UI language
    $ = jQuery;
    lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('employment-opportunity-request-form-page')) {
      AgriHelper.page_type = 'add-empl-special';
    }
    else if ($('body').hasClass('news-article-form-page')) {
      AgriHelper.page_type = 'add-news-special';
    }

    console.log(AgriHelper.page_type);
    previewInNewTab();

    //remove the data-toggle from the links anchors in main navigation for media or large devices

    initialized = true;
  }

  /*
    validate news form and EO form
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
    $('input[required="required"]').each(function(index, element) {
      if ($(element).hasClass('form-text')) {
        if ($(element).val().length == 0) {
          AgriHelper.form_required_valid = false;
          return AgriHelper.form_required_valid;
        }
      }
    });
  }
  /**
   * Set up preview in new tab.
   */
  function previewInNewTab() {
    if (AgriHelper.page_type == 'add-empl-special' || AgriHelper.page_type == 'add-news-special') {
      jQuery('#edit-preview').on('mousedown', function(e){
        formRequiredFieldsValidation();
      });

      jQuery('#edit-preview').on('mouseover', function(e){
        jQuery('.node-form').attr('target', '_blank');
      });
      jQuery('#edit-preview').on('mouseout', function(e){
        jQuery('.node-form').removeAttr('target');
      });
      jQuery('#edit-preview').on('click', function(e){
        formRequiredFieldsValidation();
        if (AgriHelper.form_required_valid == false ) {
          jQuery('.node-form').removeAttr('target');
          // Trigger the submit click instead.
          jQuery('#edit-submit').trigger('click');
          // Reset the validation for form required.
          AgriHelper.form_required_valid = true;
          //now suppress mouse click for #edit-preview.
          e.preventDefault(); // Should only need this.
          Event.stop(e); // Not sure if we need this.
          return false;
        }
        if (form_required_valid == false ) {
          Event.stop(e);
          return false;
        }
        jQuery('.node-form').attr('data-drupal-form-submit-last', '');
      });
      jQuery('#edit-submit').on('mouseover', function(e){
        jQuery('.node-form').attr('data-drupal-form-submit-last', '');
      });
    }
  }


  /**
   * Expose functions and variables
   */
  return {
    init: init,
    lang: lang,
    form_required_valid: form_required_valid,
    page_type: page_type,
  }
}();

