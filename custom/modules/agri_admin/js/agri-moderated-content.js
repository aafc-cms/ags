/**
 * @file
 * AgriSource Moderated Content behaviors.
 */

(function ($, Drupal) {
  'use strict';
  /**
   * Behavior description.
   */
  Drupal.behaviors.agriModeratedContent = {
    attach: function (context, settings) {
      if (context == document) {
        AgriModeratedContentHelper.init();
      }
    }
  };
} (jQuery, Drupal));

var AgriModeratedContentHelper = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }
    enableDisableIsPendingDraftDropdown();
    initialized = true;
  }

  /*
  * Disabel/enable the is_Pending_draft dropdown based on the Moderation State dropdown selection
  */
  function enableDisableIsPendingDraftDropdown() {
    var currentModerationState = $('select#edit-moderation-state').children("option:selected").text();
    if ((currentModerationState != 'Draft') && (currentModerationState != 'Draft/Published')  &&
        (currentModerationState != 'Brouillon') && (currentModerationState != 'Brouillon/Publié')) {
      $('select#edit-status').attr('disabled', 'disabled');
    } else {
      $('select#edit-status').removeAttr('disabled');
    }
    $('select#edit-moderation-state').change(function(){
      var selectedModerationState = $(this).children("option:selected").text();
      if ((selectedModerationState != 'Draft') && (selectedModerationState != 'Draft/Published')  &&
          (selectedModerationState != 'Brouillon') && (selectedModerationState != 'Brouillon/Publié')) {
        $('select#edit-status').attr('disabled', 'disabled');
      } else {
        $('select#edit-status').removeAttr('disabled');
      }
    })
  }

  /**
   * Expose functions and variables
   */
   return {
    init: init,
    enableDisableIsPendingDraftDropdown: enableDisableIsPendingDraftDropdown,
  }
}();
