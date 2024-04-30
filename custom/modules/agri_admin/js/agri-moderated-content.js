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
    enableDisableAAFC_SubjectDropdown();
    enableDisableDcterms_TypeDropdown();
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

  /*
  * Disabel/enable the aafc:subject metadata values dropdown based on selection option.
  */
  function enableDisableAAFC_SubjectDropdown() {
    var currentAAFC_SubjectSelection = $('select#edit-field-subject-target-id-1').children("option:selected").text();

    if ((currentAAFC_SubjectSelection != '- Any -') && (currentAAFC_SubjectSelection != '- Tout -')) {
      $('select#edit-field-subject-target-id').attr('disabled', 'disabled');
    } else {
      $('select#edit-field-subject-target-id').removeAttr('disabled');
    }

    $('select#edit-field-subject-target-id-1').change(function(){
      var selectedAAFC_SubjectSelection = $(this).children("option:selected").text();

      if ((selectedAAFC_SubjectSelection != '- Any -') && (selectedAAFC_SubjectSelection != '- Tout -')) {
        $('select#edit-field-subject-target-id').attr('disabled', 'disabled');
      } else {
        $('select#edit-field-subject-target-id').removeAttr('disabled');
      }
    })
  }

  /*
  * Disabel/enable the dcterms:type metadata values dropdown based on selection option.
  */
  function enableDisableDcterms_TypeDropdown() {
    var currentDcterms_TypeSelection = $('select#edit-field-meta-type-target-id-1').children("option:selected").text();

    if ((currentDcterms_TypeSelection != '- Any -') && (currentDcterms_TypeSelection != '- Tout -')) {
      $('select#edit-field-meta-type-target-id').attr('disabled', 'disabled');
    } else {
      $('select#edit-field-meta-type-target-id').removeAttr('disabled');
    }

    $('select#edit-field-meta-type-target-id-1').change(function(){
      var selectedDcterms_TypeSelection = $(this).children("option:selected").text();
      if ((selectedDcterms_TypeSelection != '- Any -') && (selectedDcterms_TypeSelection != '- Tout -')) {
        $('select#edit-field-meta-type-target-id').attr('disabled', 'disabled');
      } else {
        $('select#edit-field-meta-type-target-id').removeAttr('disabled');
      }
    })
  }

  /**
   * Expose functions and variables
   */
   return {
    init: init,
    enableDisableIsPendingDraftDropdown: enableDisableIsPendingDraftDropdown,
    enableDisableAAFC_SubjectDropdown: enableDisableAAFC_SubjectDropdown,
    enableDisableDcterms_TypeDropdown: enableDisableDcterms_TypeDropdown,
  }
}();
