/**
 * @file
 * emplNode behaviors.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.emplNode = {
    attach: function (context, settings) {
      if (context == document) {
        Emplnode.init();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);


var Emplnode = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var page_type = 'content';
  var data = [];

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }
    return; // Hack is disabled DO NOT DO HACKS LIKE THIS!.

    // Get the current UI language
    $ = jQuery;
    Emplnode.lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('page-node-type-empl')) {
      Emplnode.page_type = 'empl'; // Other admin page.
    }

    if (Emplnode.page_type == 'empl') {
      //$(document).on('mousemove', onMouseMove);

      // BEGIN HACK CODE.
      //Replace -- Services and Information with '', should only be Human Resources.

      if (Emplnode.lang == 'en') {
        $('ol.breadcrumb a[href="/en/human-resources"').each(function(index, el) {
          if (jQuery(el).length > 0) {
            $(el).text('Human Resources');
          }
        });
      }
      else {
        $('ol.breadcrumb a[href="/fr/ressources-humaines"').each(function(index, el) {
          if (jQuery(el).length > 0) {
            $(el).text('Ressources humaines');
          }
        });
      }
      // END HACK CODE. , this code is no longer needed, don't do this, see agri_admin.module line 750 instead.
    }

    initialized = true;
  }


  /**
   * logCall().
   **/
  function logCall(funcName, force) {
    if (typeof data[funcName] == 'undefined') {
      Emplnode.data[funcName] = 0;
    }
    if (typeof force == 'undefined') {
      force = false;
    }
    Emplnode.data[funcName]++;
    var debug = true; // Debug is disabled.
    if (debug || force) {
      console.log(funcName + ' call:' + Emplnode.data[funcName]);
    }
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
    logCall: logCall,
    page_type: page_type
  }
}();

