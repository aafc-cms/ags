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
        AgriReportsHelper.init();
      }
    }
  };
} (jQuery, Drupal));

var AgriReportsHelper = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }
    enableDisableAAFC_SubjectDropdown();
    enableDisableDcterms_TypeDropdown();
    initialized = true;
  }

  /*
  * Disabel/enable the metadata values dropdown based on selection option.
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
    enableDisableAAFC_SubjectDropdown: enableDisableAAFC_SubjectDropdown,
    enableDisableDcterms_TypeDropdown: enableDisableDcterms_TypeDropdown,
  }
}();
