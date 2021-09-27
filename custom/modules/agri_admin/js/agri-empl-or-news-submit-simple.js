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
        AgriNewsEmplSimple.init();
      }
      else if ($('body').hasClass('node-add') &&
        $('#edit-preview').length ) {
        AgriNewsEmplSimple.init();
      }
    }
  };

} (jQuery, Drupal));

var AgriNewsEmplSimple = function() {
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
    console.log('initialize agri_admin/js/agri-empl-or-news-submit-simple.js');

    // Get the current UI language
    $ = jQuery;
    lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('nodeaddnews')) {
      AgriNewsEmplSimple.page_type = 'add-news-special';
    }
    if ($('body').hasClass('nodeaddempl')) {
      AgriNewsEmplSimple.page_type = 'add-empl-special';
    }
    //Determine the page type
    if ($('body').hasClass('employment-opportunity-request-form-page')) {
      AgriNewsEmplSimple.page_type = 'add-empl-special';
    }
    else if ($('body').hasClass('news-article-form-page')) {
      AgriNewsEmplSimple.page_type = 'add-news-special';
    }

    console.log(AgriNewsEmplSimple.page_type);
    syncModerationStates();

    //remove the data-toggle from the links anchors in main navigation for media or large devices

    initialized = true;
  }

  function syncModerationStates() {
    if ($('body').hasClass('role-is-authenticated')) {
      var bothStatus = $("#edit-moderation-state-0-state, #edit-moderation-state-etuf-fr-0-state");
      //edit-moderation-state-etuf-fr-0-state
      //edit-moderation-state-0-state
      bothStatus.change(function(e) {
        bothStatus.val(this.value); // "this" is the changed one
      });
    }
  }


  /**
   * Expose functions and variables
   */
  return {
    init: init,
    lang: lang,
    page_type: page_type,
  }
}();

