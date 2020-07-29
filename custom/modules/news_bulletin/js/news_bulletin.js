(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.newsbulletin = {
    attach: function (context, settings) {
      logCall(arguments.callee.name.toString());
      //logCall('Drupal.behaviors.newsbulletin attach');
      if (context === document) {
        console.log('Drupal.behaviors.newsbulletin attach context === document');
        NewsBulletin.init(); // Initialize only when context === document.
        //below is for the demo.
      } else {
        // Functions that need to be called on other attach calls (ex: by ajax) should be added here.
      }
    }
  };
})(jQuery, Drupal, drupalSettings);

var data = [];
function logCall(funcName, force) {
  if (typeof data[funcName] == 'undefined') {
    data[funcName] = 0;
  }
  if (typeof force == 'undefined') {
    force = false;
  }
  data[funcName]++;
  var debug = true; // Debug is disabled.
  if (debug || force) {
    console.log(funcName + ' call:' + data[funcName]);
  }
}

/**
 * Class to handle the bulletin page.
 */
var NewsBulletin = function() {
  var newTab = null;  // Reference to new tab window.
  var nidCount = 0;
  var nodeCount = 0;
  var tempNid = null;
  var tempTid = null;
  var movedNidItem = null;
  var movedNidNodeName = null;
  var movedItem = null;        // movedItem (Draggable)
  var movedItemNodeName = null;        // movedItem (Draggable)
  var movedItemTid = null;     // movedItem (Draggable) tid (term id) .
  var newsNids = [];
  var newsTypeArray = [];
  var tidsArray = [];
  var newsbulletin = {
    initialized: false,     // Flag to indicate that this class has been initialized
    lang: 'en',             // language that the content is in
    loggedIn: false,        // Current user on this thread is logged in.
  };

  /**
   * Initialization (one time)
   */
  function init() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (newsbulletin.initialized) {
      return;
    }

    //var elementsSortable = jQuery("table.table-news-bulletin");
    var elementsSortable = jQuery("table");
    jQuery(elementsSortable).each(function(index, element) {
      Sortable.create(element, {
        group: "sorting",
        sort: true,
        //handle: "thead",
        filter: 'tbody, td',
        emptyInsertThreshold: 1,
        direction: 'vertical',
        // Element dragging started
        onStart: function (/**Event*/evt) {
          var movedItem = evt.item;
          if (movedItem.nodeName == 'THEAD') {
            console.log('NewsTypesSortable ' + arguments.callee.name.toString());
            //evt.oldIndex;  // element index within parent
            NewsBulletin.newsTypeArray = []; // Reset the array, important!
            NewsBulletin.newsNids = []; // Reset the array, important!
          }
        },
        onSort: function(evt) {
          var movedItem = evt.item;
          if (movedItem.nodeName == 'THEAD') {
            NewsBulletin.movedItem = movedItem;
            NewsBulletin.movedItemNodeName = NewsBulletin.movedItem.nodeName;
            console.log('NewsTypesSortable ' + arguments.callee.name.toString());
            NewsBulletin.movedItemTid = jQuery(movedItem).attr('data-attribute-group');
            console.log('movedItemTid ' + NewsBulletin.movedItemTid);
            if (jQuery(NewsBulletin.movedItem).parent().nodeName == 'TBODY') {
              //jQuery(NewsBulletin.movedItem).detach().insertAfter(jQuery(NewsBulletin.movedItem).parent());
            }
            var tempItem = jQuery('table tbody.has-row-data').each(function(index, element) {
              if (jQuery(element).attr('data-attribute-group') == NewsBulletin.movedItemTid) {
                if (NewsBulletin.movedItemNodeName == 'THEAD') {
                  jQuery(element).detach().insertAfter(NewsBulletin.movedItem);
                }
                console.log('item that was moved is found , the tid=' + NewsBulletin.movedItemTid);
              }
              else {
                // Workaround for a glitch where the other tbody elements are in the wrong order.
                var tid_other_group = jQuery(element).attr('data-attribute-group');
                var other_group_element = jQuery("thead[data-attribute-group='" + tid_other_group + "']");
                jQuery(element).detach().insertAfter(other_group_element);
              }
            });

            // Enable the ajax throbber / spinner for show busy. (moved)
            //$(NewsBulletin.movedItem).after(Drupal.theme.ajaxProgressThrobber(Drupal.t('Updating order of groups, one moment please.')));
            NewsBulletin.setNewsTypeOrder();
            console.log('NewsBulletin.newsTypeArray=' + NewsBulletin.newsTypeArray.toString());
          }
          else {
            console.log('movedNodeName =' + NewsBulletin.movedItemNodeName + ' therefore no logic to do here, testing 123');
          }
        }
      });
    });
    setupNodeSort();

    initWhenReady();
    newsbulletin.initialized = true;
    return newsbulletin.initialized;
  }

  /**
   * Initialization phase 2. Waits for the googleApiKey to be set and then continues initialization.
   */
  function initWhenReady() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    newsbulletin.lang = jQuery('html').attr('lang');
    // Activate the show-more button
    jQuery('table.table input').each(function(index, element) {
      jQuery(element).click(function() {
        logCall(arguments.callee.name.toString());
        //$(this).after(Drupal.theme.ajaxProgressThrobber(Drupal.t('Updating selections, one moment please.')));
        NewsBulletin.setNewsTypeOrder();
      });
    });
  }

  function setupNodeSort() {
    tallyTids();
    var tbodyRowsSortable = jQuery("table tbody").each(function(index, element) {
      setupSortableNodes(element);
    });
  }

  function setupSortableNodes(element, nid) {
    logCall(arguments.callee.name.toString());
    //console.log('setupSortableNodes nid=' + nid);
    Sortable.create(element, {
      group: "sorting",
      sort: true,
      direction: 'vertical',
      // Element dragging started
      onStart: function (/**Event*/evt) {
        logCall('setupSortableNodes ' + arguments.callee.name.toString());
        //evt.oldIndex;  // element index within parent
      },
      onSort: function(evt) {
        logCall('setupSortableNodes ' + arguments.callee.name.toString());
        var movedItem = evt.item;
        console.log('movedItemTid ' + movedItem);
        var tempTest = jQuery(movedItem).attr('data-attribute-group');
        console.log('joseph temptest=' + tempTest);

        // Enable the ajax throbber / spinner for show busy. (moved)
        //$(NewsBulletin.movedItem).after(Drupal.theme.ajaxProgressThrobber(Drupal.t('Updating order of groups, one moment please.')));
        NewsBulletin.setNewsTypeOrder();
        console.log('NewsBulletin.newsTypeArray=' + NewsBulletin.newsTypeArray.toString());
      }
    });
  }

  function resetForm() {
    jQuery('form#news-bulletin-form-1').removeAttr('data-drupal-form-submit-last');
  }

  function tallyTids() {
    NewsBulletin.tidsArray = [];
    //data-attribute-group:
    jQuery('table.table thead').each(function(index, element) {
      // Build the array of news type tids, assuming each works in element order on DOM.
      NewsBulletin.nodeCount = 0;
      NewsBulletin.tempTid = jQuery(element).attr('data-attribute-group');
      NewsBulletin.tidsArray.push(NewsBulletin.tempTid);
    });
  }


  function tallySelections() {
    resetForm();
    NewsBulletin.newsNids = [];
    NewsBulletin.newsTypeArray = [];
    //data-attribute-group:
    jQuery('table.table thead').each(function(index, element) {
      // Build the array of news type tids, assuming each works in element order on DOM.
      NewsBulletin.nodeCount = 0;
      NewsBulletin.tempTid = jQuery(element).attr('data-attribute-group');
      jQuery('table.table input[type=checkbox]:checked').not(':disabled').each(function(indexOfInputElements, inputElement) {
        // Build the array of news type tids, assuming each works in element order on DOM.
        if (NewsBulletin.tempTid == jQuery(inputElement).closest('tr').attr('data-group-current')) {
          NewsBulletin.nodeCount++;
        }
      });
      if (NewsBulletin.nodeCount > 0) {
        // Only include the type if it has nodes, this one does!
        NewsBulletin.newsTypeArray.push(NewsBulletin.tempTid);
      }

    });

    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    jQuery('table.table input[type=checkbox]:checked').not(':disabled').each(function(index, element) {
      // Build the array of news type tids, assuming each works in element order on DOM.
      NewsBulletin.newsNids.push(jQuery(element).attr('value'));
    });
    var enabledNodeCount = jQuery('table.table input[type=checkbox]:checked').not(':disabled').length;
    console.log('enabled news items = ' + enabledNodeCount);
    console.log('NewsBulletin.newsNids = ' + NewsBulletin.newsNids.join());
    console.log('NewsBulletin.newsTypeArray = ' + NewsBulletin.newsTypeArray.join());
  }


  /**
   * Set order of news types.
   */
  function setNewsTypeOrder() {
    jQuery('html, body').css("cursor", "wait");
    if (Drupal.theme.ajaxProgressIndicatorFullscreen) {
      jQuery('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen(Drupal.t('Updating order of groups, one moment please.')));
    }
    else {
      console.log('Where is Drupal.theme.ajaxProgressIndicatorFullscreen ?');
    }

    tallySelections();
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.

    /*  This ajax option works very well instead of ?= and &= if order isn't important.  However order is important so using .join instead.
    data: {
      'tids': NewsBulletin.newsTypeArray,
      'nids': NewsBulletin.newsNids
    },
    */
    var termsParam = '';
    if (NewsBulletin.newsTypeArray.length) {
      termsParam = '?tids=' + NewsBulletin.newsTypeArray.join();
    }
    var nidsParam = '';
    if (NewsBulletin.newsNids.length) {
      nidsParam = '&nids=' + NewsBulletin.newsNids.join();
      if (!termsParam.length) {
        nidsParam = '?nids=' + NewsBulletin.newsNids.join();
      }
    }
    // Make the agax call to update the temp store.
    /*      data: {
            'nids': NewsBulletin.newsNids
          }*/
    jQuery.ajax({
      url: '/news-at-work-bulletin/set_temp_config' + termsParam + nidsParam,
      type: 'GET',
      success: function(response) {
        console.log(response);
        // Disable the ajax throbber / spinner for show busy.
        jQuery('html, body').css("cursor", "auto");
        jQuery('div.ajax-progress').remove(".ajax-progress-throbber"); // Remove the throbber like this.
        jQuery('div.ajax-progress').remove(".ajax-progress-fullscreen"); // Remove the throbber like this.
        jQuery('input').each(function(index,element) {
          jQuery(element).remove(".ajax-progress-throbber"); // Remove the throbber like this.
        });
      },
      error: function(message) {
        console.log('ajax error');
        jQuery('html, body').css("cursor", "auto");
        jQuery('div.ajax-progress').remove(".ajax-progress-throbber"); // Remove the throbber like this.
        jQuery('div.ajax-progress').remove(".ajax-progress-fullscreen"); // Remove the throbber like this.
        //$(NewsBulletin.movedItem).after(Drupal.theme.ajaxProgressMessage(Drupal.t('Error with ajax call in setNewsTypeOrder().')));
        if (Drupal.theme.ajaxProgressMessage) {
          jQuery('table.table').after(Drupal.theme.ajaxProgressMessage(Drupal.t('Error with ajax call in setNewsTypeOrder().')));
        }
        else {
          console.log('Where is Drupal.theme.ajaxProgressMessage()?')
        }
      }
    });
  }


  /**
   * Expose functions and variables
   */
  return {
    init: init,
    newsbulletin: newsbulletin,
    newTab: newTab,
    nodeCount: nodeCount,
    nidCount: nidCount,
    tempNid: tempNid,
    tempTid: tempTid,
    movedItem: movedItem,
    movedItemTid: movedItemTid,
    movedNidItem: movedNidItem,
    movedNidNodeName: movedNidNodeName,
    newsTypeArray: newsTypeArray,
    tidsArray: tidsArray,
    newsNids: newsNids,
    setNewsTypeOrder: setNewsTypeOrder,
    movedItemNodeName: movedItemNodeName
  }
}();
