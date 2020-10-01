/**
 * @file
 * contentPluginsChanges behaviors.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.contentPluginsChanges = {
    attach: function (context, settings) {
      if (context == document) {
        ContentPluginsChanges.init();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);


var ContentPluginsChanges = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var mouse = {x:0, y:0};    // Tracks the mouse position
  var page_type = 'content';
  var temp_element = null;
  var data = [];

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }

    console.log('initialize ContentPluginsChanges');
    // Get the current UI language
    $ = jQuery;
    ContentPluginsChanges.lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('path-admin')) {
      ContentPluginsChanges.page_type = 'admin'; // Other admin page.
    }

    if (ContentPluginsChanges.page_type == 'admin') {
      $(document).on('mousemove', onMouseMove);


      // Reorder select items.
      $('select#edit-action option').each(function(index, el) {
        if ($(el).attr('value') == 'node_break_lock_action') {
          $("select#edit-action").val("node_break_lock_action"); // set the default action to node break lock action.
          $('select#edit-action option[value=archive_current]').insertAfter($('select option[value=node_break_lock_action]'));
        }
      });

      // Reorder select items.
      $('select#edit-action option').each(function(index, el) {
        if ($(el).attr('value') == 'convert_bundles_on_node') {
          //ContentPluginsChanges.temp_element = $(el);
          $(el).insertAfter($('select option[value=archive_current]'));
        }
        if ($(el).attr('value') == 'convert_bundles_on_node') {
          if (!$('body').hasClass('role-is-administrator')) {
            $(el).attr('disabled', 'disabled');
          }
        }
      });
      
      
    }

    initialized = true;
  }


  /**
   * logCall().
   **/
  function logCall(funcName, force) {
    if (typeof data[funcName] == 'undefined') {
      ContentPluginsChanges.data[funcName] = 0;
    }
    if (typeof force == 'undefined') {
      force = false;
    }
    ContentPluginsChanges.data[funcName]++;
    var debug = true; // Debug is disabled.
    if (debug || force) {
      console.log(funcName + ' call:' + ContentPluginsChanges.data[funcName]);
    }
  }


  /**
   * Keep track of mouse movements.
   */
  function onMouseMove(event) {
    ContentPluginsChanges.mouse.x = event.clientX;
    ContentPluginsChanges.mouse.y = event.clientY;
  }


  function isIE() {
    if (navigator.appName == 'Microsoft Internet Explorer') {
      return true;
    }
    else if (navigator.appName == 'Netscape') {
      if (navigator.appVersion.indexOf('Trident') > 0 || navigator.appVersion.indexOf('Edge') > 0) {
        return true;
      }
    }
    return false;
  }


  /**
   * Expose functions and variables
   */
  return {
    init: init,
    lang: lang,
    data: data,
    temp_element: temp_element,
    logCall: logCall,
    mouse: mouse,
    page_type: page_type,
  }
}();

