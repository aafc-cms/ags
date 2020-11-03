/**
 * @file
 * emplNode behaviors.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.emplOppView = {
    attach: function (context, settings) {
      if (context == document) {
        EmplOpp.init();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);


var EmplOpp = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var delay = 100;
  var columnElement = null;
  var page_type = 'content';
  var data = [];

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }

    // Get the current UI language
    $ = jQuery;
    EmplOpp.lang = $('html').attr('lang');

    //Determine the page type
    if ($('div.view-view-employmentopportunities').length > 0) {
      EmplOpp.page_type = 'employment-opportunities'; // Other admin page.
    }

    if (EmplOpp.page_type == 'employment-opportunities') {
      // Investigated, wet-boew interferes with this type of a table so as a workaround have to trigger a sort.
      EmplOpp.canWeSortYet();
    }

    initialized = true;
  }


  /**
   *  Call this when wet-boew.js has done it's business.
   */
  function sortByClosingDate() {
    EmplOpp.columnElement.trigger('click');
  }


  function canWeSortYet() {
    // Wet-boew is enabling this table, wait until it's done.
    if (typeof EmplOpp.columnElement === 'undefined') {
      EmplOpp.columnElement = $('#view-field-date-closing-table-column');
    }
    if (!EmplOpp.columnElement.hasClass('sorting') && !EmplOpp.columnElement.hasClass('sorting_asc')) { 
       // Wet-boew is not yet done initializing this table, wait until it's done.
       if (EmplOpp.delay < 2000) {
         // Only try until 2000 ms, to keep load down on browsers.
         window.setTimeout(EmplOpp.canWeSortYet, EmplOpp.delay); /* Checks every (delay) milliseconds*/
       }
       EmplOpp.delay+=50; // Only try a few times.
    } else {
      // Sort, now that wet-boew has done it's sorting.
      EmplOpp.sortByClosingDate();
      // Done!
    }
  }


  function findMyEvents(me) {
    // Debugging function.
    if (typeof $._data($(me)[0], 'events') !== "undefined") {
        console.log($(me)[0])
        console.log($._data($(me)[0], 'events'))
        $._data($(me)[0], 'events')
    };
    for (var i = 0; i < $(me).children().length; i++) {
        findMyEvents($(me).children()[i])
    }
  }

  /**
   * logCall().
   **/
  function logCall(funcName, force) {
    if (typeof data[funcName] == 'undefined') {
      EmplOpp.data[funcName] = 0;
    }
    if (typeof force == 'undefined') {
      force = false;
    }
    EmplOpp.data[funcName]++;
    var debug = false; // Debug is disabled.
    if (debug || force) {
      console.log(funcName + ' call:' + EmplOpp.data[funcName]);
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
    canWeSortYet: canWeSortYet,
    data: data,
    delay: delay,
    sortByClosingDate: sortByClosingDate,
    findMyEvents: findMyEvents,
    logCall: logCall,
    page_type: page_type
  }
}();

