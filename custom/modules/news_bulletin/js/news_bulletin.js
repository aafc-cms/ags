(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.newsbulletin = {
    attach: function (context, settings) {
      logCall(arguments.callee.name.toString());
      //logCall('Drupal.behaviors.newsbulletin attach');
      if (context === document) {
        logCall('Drupal.behaviors.newsbulletin attach context === document');
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
  var nodeCount = 0;
  var tempTid = null;
  var movedItem = null;        // movedItem (Draggable)
  var movedItemNodeName = null;        // movedItem (Draggable)
  var movedItemTid = null;     // movedItem (Draggable) tid (term id) .
  var newsNids = [];
  var newsTypeArray = [];
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
        filter: 'tbody',
        direction: 'vertical',
        // Element dragging started
        onStart: function (/**Event*/evt) {
          logCall(arguments.callee.name.toString());
          //evt.oldIndex;  // element index within parent
          NewsBulletin.newsTypeArray = []; // Reset the array, important!
          NewsBulletin.newsNids = []; // Reset the array, important!
        },
        onSort: function(evt) {
          logCall(arguments.callee.name.toString());
          var movedItem = evt.item;
          NewsBulletin.movedItem = movedItem;
          NewsBulletin.movedItemNodeName = NewsBulletin.movedItem.nodeName;
          NewsBulletin.movedItemTid = jQuery(movedItem).attr('data-attribute-group');
          console.log('movedItemTid ' + NewsBulletin.movedItemTid);
          var tempItem = jQuery('table tbody.has-row-data').each(function(index, element) {
            if (jQuery(element).attr('data-attribute-group') == NewsBulletin.movedItemTid) {
              if (NewsBulletin.movedItemNodeName == 'THEAD') {
                jQuery(element).detach().insertAfter(NewsBulletin.movedItem);
              }
              logCall('item that was moved is found , the tid=' + NewsBulletin.movedItemTid);
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
      });
    });
    /*
    var elementsSortable = jQuery(".news-bulletin-test");
    jQuery(elementsSortable).each(function(index, element) {
      Sortable.create(element, {
        group: "sorting",
        sort: true,
        direction: 'vertical'
      });
    });*/

/*    var groupsSortable = document.getElementById("news-bulletin-list");//[data-attribute-group]
    Sortable.create(groupsSortable, {
      group: "sorting",
      sort: true,
      draggable: '.glyphicon-move',
      direction: 'vertical'
    });
*/

    initWhenReady();
    newsbulletin.initialized = true;
    return;

  }

  /**
   * Initialization phase 2. Waits for the googleApiKey to be set and then continues initialization.
   */
  function initWhenReady() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    newsbulletin.lang = $('html').attr('lang');
    // Activate the show-more button
    $('table.table input').each(function(index, element) {
      jQuery(element).click(function() {
        logCall(arguments.callee.name.toString());
        //$(this).after(Drupal.theme.ajaxProgressThrobber(Drupal.t('Updating selections, one moment please.')));
        NewsBulletin.setNewsTypeOrder();
      });
    });
  }

  function resetForm() {
    jQuery('form#news-bulletin-form-1').removeAttr('data-drupal-form-submit-last');
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
      $('table.table input[type=checkbox]:checked').not(':disabled').each(function(indexOfInputElements, inputElement) {
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
    $('table.table input[type=checkbox]:checked').not(':disabled').each(function(index, element) {
      // Build the array of news type tids, assuming each works in element order on DOM.
      NewsBulletin.newsNids.push(jQuery(element).attr('value'));
    });
    var enabledNodeCount = $('table.table input[type=checkbox]:checked').not(':disabled').length;
    console.log('enabled news items = ' + enabledNodeCount);
    console.log('NewsBulletin.newsNids = ' + NewsBulletin.newsNids.join());
    console.log('NewsBulletin.newsTypeArray = ' + NewsBulletin.newsTypeArray.join());
  }
  /**
   * Display a set of video thumbnails
   */
  function displayThumbs() {
    // Tee up the current item.
    if (newsbulletin.currentBulletin >= 0 && newsbulletin.bulletinList[newsbulletin.currentBulletin]) {
      $('.news-bulletin .sidebar').append('<h2>' + newsbulletin.bulletinListt[newsbulletin.currentBulletin].title + '</h2><p>' + newsbulletin.bulletinListt[newsbulletin.currentBulletin].description + '</p>');
      var bid_id = newsbulletin.bulletinListt[newsbulletin.currentBulletin].id;
      var ajax_url = (newsbulletin.lang == 'fr' ? '/fr' : '/en') + '/rest/views/news-bulletin-rest';
      logCall('ajax_url =' + ajax_url);
      $.ajax({
        url: ajax_url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
        },
        error: function() {
        }
      });
    }
  }

  /**
   * Set order of news types.
   */
  function setNewsTypeOrder() {
    $('html, body').css("cursor", "wait");
    $('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen(Drupal.t('Updating order of groups, one moment please.')));
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
    $.ajax({
      url: '/news-at-work-bulletin/set_temp_config?tids=' + NewsBulletin.newsTypeArray.join() + nidsParam,
      type: 'GET',
      data: {
        'nids': NewsBulletin.newsNids
      },
      success: function(response) {
        logCall(response);
        // Disable the ajax throbber / spinner for show busy.
        $('html, body').css("cursor", "auto");
        $('div.ajax-progress').remove(".ajax-progress-throbber"); // Remove the throbber like this.
        $('div.ajax-progress').remove(".ajax-progress-fullscreen"); // Remove the throbber like this.
        $('input').each(function(index,element) {
          $(element).remove(".ajax-progress-throbber"); // Remove the throbber like this.
        });
      },
      error: function(message) {
        logCall('ajax error');
        $('html, body').css("cursor", "auto");
        $('div.ajax-progress').remove(".ajax-progress-throbber"); // Remove the throbber like this.
        $('div.ajax-progress').remove(".ajax-progress-fullscreen"); // Remove the throbber like this.
        //$(NewsBulletin.movedItem).after(Drupal.theme.ajaxProgressMessage(Drupal.t('Error with ajax call in setNewsTypeOrder().')));
        $('table.table').after(Drupal.theme.ajaxProgressMessage(Drupal.t('Error with ajax call in setNewsTypeOrder().')));
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
    tempTid: tempTid,
    movedItem: movedItem,
    movedItemTid: movedItemTid,
    newsTypeArray: newsTypeArray,
    newsNids: newsNids,
    setNewsTypeOrder: setNewsTypeOrder,
    movedItemNodeName: movedItemNodeName
  }
}();
