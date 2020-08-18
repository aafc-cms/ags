/**
 * @file
 * Agri Admin Validation behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.agriAdminMenuNavigation = {
    attach: function (context, settings) {
      if ($('body.admin-structure').length) {
        // Only initialize in this situation.
        MenuNavigation.init();
      }
    }
  };

} (jQuery, Drupal));

var MenuNavigation = function() {
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
    console.log('initialize agri_admin/js/menu-navigation.js');
    // Get the current UI language
    $ = jQuery;
    MenuNavigation.lang = $('html').attr('lang');

    initializeLinkDisabler();
    initialized = true;
  }


 /**
  *  Validate bulletin form.
  */
  function initializeLinkDisabler() {
    // Hide disabled menu links
    if ($('.menu-link-content-form').length) {
      var disabledMenuLinkHtml = '<form id="menu-disabled-links-form" action="#nothing">' +
      '<input type="checkbox" class="form-boolean--type-checkbox form-checkbox form-boolean" id="menu-disabled-links-switch" name="menu-disabled-links-switch" value="enabled" checked>' +
      '<label for="menu-disabled-links-switch" class="show-disabled form-item__label"> ' + Drupal.t('Hide disabled items') + '</label>' +
      '</form>';
      $('#edit-menu-parent--description').first().append(disabledMenuLinkHtml);
      $('#edit-menu-parent option').each(function(index, element) {
        var aiguille = MenuNavigation.lang == 'en' ? 'disabled)' : 'désactivé)';
        if (~$(element).text().indexOf(aiguille)) {
          $(element).hide();
        }
      });
      $("#menu-disabled-links-form").show();
      $("#menu-disabled-links-switch").click(function(e) {
        MenuNavigation.handleClickEvent(e);
      });
      $("#menu-disabled-links-form").show();
    }
    else {
      console.log('agri_admin/js/menu-navigation.js element with class menu-link-content-form  not found');
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
  function handleClickEvent(e) {
    console.log('handleShowHide click');
    $('select.menu-title-select').first().find('option').each(function(index, element) {
      if ($(element).text().indexOf('isabled)') > 0) {
        var is_hidden = $(element).css('display') == 'none';
        if (is_hidden) {
          $(element).show();
        }
        else {
          $(element).hide();
        }
      }
    });
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

