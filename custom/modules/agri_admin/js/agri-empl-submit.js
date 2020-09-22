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
  var checkbox_valid = false;  // Extra flag for processing in anonymous functions.

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

    //added preview and submit button event handler
    $('#edit-preview').click(function(){ handlePreviewClickEvent();})
    $('#edit-submit').click(function(){ handleSubmitClickEvent();});

    console.log(AgriHelper.page_type);

    initialized = true;
  }

  function addSubmitClickHandler() {
  }


  /*
    validate empl (EO) form.
  */
  function formRequiredFieldsValidation() {
    AgriHelper.checkbox_valid = false;
    $('div[id="edit-field-type"]').children().find('input').each(function(index, element) {
      if ($(element).hasClass('form-checkbox')) {
        if ( $(element).prop('checked') && (!AgriHelper.checkbox_valid)) {
           AgriHelper.checkbox_valid = true;
        }
      }
    });

    AgriHelper.form_required_valid =  AgriHelper.checkbox_valid;
    console.log ( AgriHelper.checkbox_valid);
    var errlbl = $('#edit-emplopptypes-0-value-error');
    var errlblexists  = errlbl.length;
    if (!AgriHelper.checkbox_valid && !errlblexists) {
     // inject error label after the last checkbox to place close to the input field
      var errfieldlbl = ''; 
      if (lang == 'fr') {
        errfieldlbl = '<label id="edit-emplopptypes-0-value-error" for="edit-emplopptypes-0-value-error" style="display: block; color: #a94442;">Type de possibilité di\'emploi field est requis.</label>';
      } else {
        errfieldlbl = '<label id="edit-emplopptypes-0-value-error" for="edit-emplopptypes-0-value-error" style="display: block; color: #a94442;">Employment Opportunity Type(s) field is required.</label>';
      }
      $('div#edit-field-type[class="form-checkboxes"] div').last().after(errfieldlbl);
      // make the validation error be close to the input field
      $('div#edit-field-type[class="form-checkboxes"] div').last().css({"margin-bottom": "0px"});
    } else
    {
      if (AgriHelper.checkbox_valid && errlblexists) {
        // hide the message
         $('#edit-emplopptypes-0-value-error').hide();
         $('div#edit-field-type[class="form-checkboxes"] div').last().css({"margin-bottom": "10px"});
      } else {
        if (!AgriHelper.checkbox_valid && errlblexists) {
        //we need to bring it back
          $('#edit-emplopptypes-0-value-error').show();
          $('div#edit-field-type[class="form-checkboxes"] div').last().css({"margin-bottom": "0px"});
        }
      }
    }

    return AgriHelper.form_required_valid;

  /*$('select[required="required"]').each(function(index, element) {
      if ($(element).hasClass('form-select')) {
        if ($(element).val() == '_none') {
          AgriHelper.form_required_valid = false;
          return AgriHelper.form_required_valid;
        }
      }
    });
    */
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
    checkbox_valid: checkbox_valid,
    handleSubmitClickEvent: handleSubmitClickEvent,
    handlePreviewClickEvent: handlePreviewClickEvent,
    page_type: page_type,
  }
}();

