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
  var debug = false; // Debug is disabled.
  if (debug || force) {
    console.log(funcName + ' call:' + data[funcName]);
  }
}

/**
 * Class to handle the bulletin page.
 */
var NewsBulletin = function() {
  var firstResult = true;
  var newTab = null;  // Reference to new tab window.
  var nidCount = 0;
  var nodeCount = 0;
  var tempNid = null;
  var tempTid = null;
  var sourceRowIdx = null;
  var destRowIdx = null;
  var movedNidItem = null;
  var movedNidNodeName = null;
  var movedItem = null;        // movedItem (Draggable)
  var movedItemNodeName = null;        // movedItem (Draggable)
  var movedItemTid = null;     // movedItem (Draggable) tid (term id) .
  var nodeTid = null;
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
            // console.log('NewsTypesSortable ' + arguments.callee.name.toString());
            //evt.oldIndex;  // element index within parent
            NewsBulletin.newsTypeArray = []; // Reset the array, important!
            NewsBulletin.newsNids = []; // Reset the array, important!
          }
        },
        onSort: function(evt) {
          var movedItem = evt.item;
          console.log(evt);
          if (movedItem.nodeName == 'THEAD') {
            if (evt.to.nodeName == 'TBODY') {
              // Fix yet another glitch in the ui, tbody should not be inserted into tbody.
              jQuery(movedItem).detach().insertAfter(jQuery(evt.to));
            }
            NewsBulletin.movedItem = movedItem;
            NewsBulletin.movedItemNodeName = NewsBulletin.movedItem.nodeName;
            console.log('NewsTypesSortable ' + arguments.callee.name.toString());
            NewsBulletin.movedItemTid = jQuery(movedItem).attr('data-attribute-group');
            console.log('movedItemTid ' + NewsBulletin.movedItemTid);

            var tempItem = jQuery('table tbody.has-row-data').each(function(index, element) {
              if (jQuery(element).attr('data-attribute-group') == NewsBulletin.movedItemTid) {
                if (NewsBulletin.movedItemNodeName == 'THEAD') {
                  jQuery(element).detach().insertAfter(NewsBulletin.movedItem);
                }
                //console.log('item that was moved is found , the tid=' + NewsBulletin.movedItemTid);
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
      if (jQuery(element).find('tr').length > 1) {
        // Only initialize the sort IF there's more than one row inside the tbody.
        jQuery(element).find('td.glyphicon').addClass('glyphicon-move');
        setupSortableNodes(element);
      }
      else {
        // Remove the move icon , nothing to move here.  For UI experience.
        jQuery(element).find('td.glyphicon-move').removeClass('glyphicon-move');
      }
    });
  }

  function setupSortableNodes(element, nid) {
    logCall(arguments.callee.name.toString());
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
        logCall('setupSortableNodes(tr rows) ' + arguments.callee.name.toString());
        NewsBulletin.movedNidNodeName = evt.item.nodeName;
        if (NewsBulletin.movedNidNodeName == 'TR') {
          NewsBulletin.sourceRowIdx = 0; // Reset each time.
          NewsBulletin.destRowIdx = 0; // Reset each time.
          NewsBulletin.movedNidItem = evt.item;
          // Make sure nodes stay in their group but allow order to be correct.
          NewsBulletin.nodeTid = jQuery(NewsBulletin.movedNidItem).attr('data-group-orig');
          NewsBulletin.tempNid = jQuery(NewsBulletin.movedNidItem).attr('data-nid');
          var parentElement = jQuery(NewsBulletin.movedNidItem).parent().first();
          var parentNodeName = '';
          if (jQuery(parentElement).get(0)) {
            parentNodeName = jQuery(parentElement).get(0).nodeName;
          }
          else if (jQuery(parentElement)[0]) {
            parentNodeName = jQuery(parentElement)[0].nodeName;
          }
          if (parentNodeName == 'TBODY' || parentNodeName == 'TABLE') {
            var groupTid = jQuery(parentElement).attr('data-attribute-group');
            if (groupTid != NewsBulletin.nodeTid) {
              console.log('Incorrect group, must fix by moving this tr element to correct group.');
              // If the movedItem is not in it's correct group we have to put it in the right group.
              // And in doing so, either as the first or last row.
              var correctTbodyElement = jQuery('.tid-' + NewsBulletin.nodeTid + ' tr.value-row').first();
              var testSourceRowIdx = jQuery('table.table tr.value-row').each(function(indexTrSource, elementTr) {
                var currentNid = jQuery(elementTr).attr('data-nid');
                if (currentNid == NewsBulletin.tempNid) {
                  NewsBulletin.sourceRowIdx = indexTrSource;
                }
              });
              var testDestRowIdx = jQuery('table.table tr.value-row').each(function(indexTr, elementTr) {
                var testParentTid = jQuery(elementTr).parent().attr('data-attribute-group');
                var currentNid = jQuery(elementTr).attr('data-nid');
                if (testParentTid == NewsBulletin.nodeTid && firstResult) {
                  NewsBulletin.firstResult = false;
                  NewsBulletin.destRowIdx = indexTr;
                }
                if (!NewsBulletin.firstResult && NewsBulletin.sourceRowIdx < NewsBulletin.destRowIdx) {
                  // Move the element in its correct place in the group.
                  var firstTargetItem = jQuery('table.table tbody.tid-' + NewsBulletin.nodeTid +' tr.value-row').first().parent();
                  // Insert the moved item inside the parent of the first tr as the first item (prepentTo).
                  jQuery(NewsBulletin.movedNidItem).detach().prependTo(firstTargetItem);
                }
                else if (!NewsBulletin.firstResult && NewsBulletin.sourceRowIdx > NewsBulletin.destRowIdx) {
                  // Move the element in its correct place.
                  var lastTargetItem = jQuery('table.table tbody.tid-' + NewsBulletin.nodeTid +' tr.value-row').last();
                  jQuery(NewsBulletin.movedNidItem).detach().insertAfter(lastTargetItem);
                }

              });
            }
          }
        }
        else {
          console.log(evt.item.nodeName);
        }

        // Enable the ajax throbber / spinner for show busy. (moved)
        //$(NewsBulletin.movedItem).after(Drupal.theme.ajaxProgressThrobber(Drupal.t('Updating order of groups, one moment please.')));
        NewsBulletin.setNewsTypeOrder();
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
    logCall(arguments.callee.name.toString());
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
      url: window.location.pathname + '/set_temp_config' + termsParam + nidsParam,
      type: 'GET',
      success: function(response) {
        //console.log(response);
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
    firstResult: firstResult,
    sourceRowIdx: sourceRowIdx,
    destRowIdx: destRowIdx,
    movedItem: movedItem,
    movedItemTid: movedItemTid,
    movedNidItem: movedNidItem,
    movedNidNodeName: movedNidNodeName,
    newsTypeArray: newsTypeArray,
    nodeTid: nodeTid,
    tidsArray: tidsArray,
    newsNids: newsNids,
    setNewsTypeOrder: setNewsTypeOrder,
    movedItemNodeName: movedItemNodeName
  }
}();
