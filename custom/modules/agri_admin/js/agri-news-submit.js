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
        AgriNewsSubmitHelper.init();
        AgriNewsSubmitHelper.sortMediaDisplayModes('[data-drupal-selector="edit-attributes-data-view-mode"]'); // Call this for all attach events.
        AgriNewsSubmitHelper.sortMediaDisplayModes('[data-drupal-selector="edit-images-thumbnail-image-style"]'); // Call this for all attach events.
      }
      else if ($('body').hasClass('node-add') &&
        $('#edit-preview').length ) {
        AgriNewsSubmitHelper.init();
      }
    }
  };

} (jQuery, Drupal));

var AgriNewsSubmitHelper = function() {
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
    console.log('initialize agri_admin/js/agri-news-submit.js');

    // Get the current UI language
    $ = jQuery;
    lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('nodeaddnews')) {
      AgriNewsSubmitHelper.page_type = 'add-news-special';
    }
    if ($('body').hasClass('nodeaddempl')) {
      AgriNewsSubmitHelper.page_type = 'add-empl-special';
    }
    //Determine the page type
    if ($('body').hasClass('employment-opportunity-request-form-page')) {
      AgriNewsSubmitHelper.page_type = 'add-empl-special';
    }
    else if ($('body').hasClass('news-article-form-page')) {
      AgriNewsSubmitHelper.page_type = 'add-news-special';
    }

    console.log(AgriNewsSubmitHelper.page_type);

    initialized = true;
  }

  function addSubmitClickHandler() {
  }


  /*
    validate news form.
  */
  function formRequiredFieldsValidation() {
    $('select[required="required"]').each(function(index, element) {
      if ($(element).hasClass('form-select')) {
        if ($(element).val() == '_none') {
          AgriNewsSubmitHelper.form_required_valid = false;
          return AgriNewsSubmitHelper.form_required_valid;
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
   * Original sort logic from https://riptutorial.com/jquery/example/11477/sorting-elements .
   */
  function sortMediaDisplayModes(selector) {
    // '[data-drupal-selector="edit-attributes-data-view-mode"]'
    var mediaStylesList = jQuery(selector);
    if (typeof mediaStylesList == 'undefined') {
      console.log('anonymous news form sortMediaDisplayModes trying to find mediaStylesList using selector ' + selector);
      return;
    }
    if (mediaStylesList) {
      var selected = jQuery(mediaStylesList).find('[selected="selected"]').detach();

      var styles = jQuery(mediaStylesList).children('option');
      var sortList = Array.prototype.sort.bind(styles);

      sortList(function(a, b) {
        // Cache inner content from the first element (a) and the next sibling (b)
        var aText = a.innerText;
        var bText = b.innerText;

        // Returning -1 will place element `a` before element `b`
        if ( aText < bText ) {
          return -1;
        }

        // Returning 1 will do the opposite
        if ( aText > bText ) {
          return 1;
        }

        // Returning 0 leaves them as-is
        return 0;
      });
      jQuery(mediaStylesList).append(jQuery(styles));
      jQuery(mediaStylesList).prepend(jQuery(selected));
    }

  }

  /**
   * Expose functions and variables
   */
  /**
   * Expose functions and variables
   */
  return {
    init: init,
    lang: lang,
    form_required_valid: form_required_valid,
    handleSubmitClickEvent: handleSubmitClickEvent,
    handlePreviewClickEvent: handlePreviewClickEvent,
    sortMediaDisplayModes: sortMediaDisplayModes,
    page_type: page_type,
  }
}();

