(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.newsbulletin = {
    attach: function (context, settings) {
      logCall('Drupal.behaviors.newsbulletin attach');
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
          //evt.oldIndex;  // element index within parent
          NewsBulletin.newsTypeArray = [];
        },
        onSort: function(evt) {
          var movedItem = evt.item;
          //console.log(movedItem);
          NewsBulletin.movedItem = movedItem;
          NewsBulletin.movedItemNodeName = NewsBulletin.movedItem.nodeName;
          NewsBulletin.movedItemTid = jQuery(movedItem).attr('data-attribute-group');
          console.log('movedItemTid ' + NewsBulletin.movedItemTid);
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
          //data-attribute-group:
          jQuery('table.table thead').each(function(index, element) {
            // Build the array of news type tids, assuming each works in element order on DOM.
            NewsBulletin.newsTypeArray.push(jQuery(element).attr('data-attribute-group'));
          });
          //$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen(Drupal.t('Updating order of groups.')));
          //$('table').after(Drupal.theme.ajaxProgressThrobber(Drupal.t('Updating order of groups, one moment please.')));
          $(NewsBulletin.movedItem).after(Drupal.theme.ajaxProgressThrobber(Drupal.t('Updating order of groups, one moment please.')));
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


    newsbulletin.initialized = true;
    return;
// sort: false
    //AgrisourceSite.showBusy();
    //initWhenReady();
    //AgrisourceSite.showNotBusy();
  }

  /**
   * Initialization phase 2. Waits for the googleApiKey to be set and then continues initialization.
   */
  function initWhenReady() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (!newsbulletin.googleApiKey) {
      setTimeout(function() { initWhenReady(); }, 100);
      //AgrisourceSite.showNotBusy();
      return;
    }

    // If this is the admin bulletin-matching page then initialize for that and return.
    if ($('body').hasClass('user-logged-in newsbulletin')) {
      newsbulletin.loggedIn = true;
      var vidMatchElement = $('div.pm-vidmatch div').first().hasClass('group-en');
      if (vidMatchElement) {
        PMVidmatch.initialize();
        return;
      }
    }

    newsbulletin.lang = $('html').attr('lang');
    newsbulletin.bulletinList = [];

    // If on the main videos page, get/display the initial set of video thumbnails.
    if ($('body').hasClass('newsbulletin')) {
      getBulletins(nbGetPages);
    }

    // Activate the show-more button
    $('.newsbulletin-pager').click(function() {
      //AgrisourceSite.showBusy();
      newsbulletin.append = true;
      newsbulletin.fromIndex = newsbulletin.bulletinList.length;
      getBulletins(1);
      return false;
    });
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
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.

    // Make the agax call to update the temp store.
    $.ajax({
      url: '/news-at-work-bulletin/set_temp_config',
      type: 'GET',
      data: {
        'tids': NewsBulletin.newsTypeArray,
        'nids': NewsBulletin.newsNids
      },
      success: function(response) {
        console.log(response);
        $('div.ajax-progress').remove(".ajax-progress-throbber"); // Remove the throbber like this.
      },
      error: function(message) {
        console.log('ajax error');
        $('div.ajax-progress').remove(".ajax-progress-throbber"); // Remove the throbber like this.
        $(NewsBulletin.movedItem).after(Drupal.theme.ajaxProgressMessage(Drupal.t('Error with ajax call in setNewsTypeOrder().')));
      }
    });
  }


  /**
   * Expose functions and variables
   */
  return {
    init: init,
    newsbulletin: newsbulletin,
    movedItem: movedItem,
    movedItemTid: movedItemTid,
    newsTypeArray: newsTypeArray,
    newsNids: newsNids,
    setNewsTypeOrder: setNewsTypeOrder,
    movedItemNodeName: movedItemNodeName
  }
}();
