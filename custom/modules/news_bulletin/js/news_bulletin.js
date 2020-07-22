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
  var newsbulletin = {
    initialized: false,     // Flag to indicate that this class has been initialized
    lang: 'en',             // language that the content is in
    nCols: 4,               // Number of thumbnail columns
    nbGetPages: 0,          // Current page.
    bulletinList: [],            // Contains the list of bulletin retrieved thus far from youtube
    currentBulletin: 0,          // The index (zero-based) of the currently-selected bulletin
    loggedIn: false,        // Current user on this thread is logged in.
    scrollToCurrent: false, // Indicates whether to scroll to current bulletin after a re-display
    append: false,          // Hack: This flag indicates that the next displayThumbs() should append the curent page
    fromIndex: 0            // Hack: This var specifies what index to begin displaying thumbnails in append mode
  };

  /**
   * Initialization (one time)
   */
  function init() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (newsbulletin.initialized) {
      return;
    }

    var elementsSortable = jQuery("table.table-news-bulletin");
    jQuery(elementsSortable).each(function(index, element) {
      Sortable.create(element, {
        group: "sorting",
        sort: true,
        direction: 'vertical'
      });
    });


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
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    var fromIndex = 0;
    if (newsbulletin.append) {
      fromIndex = newsbulletin.fromIndex;
      newsbulletin.fromIndex = 0;
      newsbulletin.append = false;
    }
    else {
      // Remove all existing thumbnail bootstrap-rows
      $('.news-bulletin .bs-thumb-row').remove();
    }

    // Get the micro-templates
    var row_template = $('#video-row').text();
    var card_template = $('#bulletin-card-single').text();

    // Initalize vars
    var new_row = true;
    var row = null;
    var row_num = 0; // Need to keep a separate row_num and row_id as they have slightly different roles.
    var row_id = parseInt(fromIndex / newsbulletin.nCols);
    var n_thumb = 0;
    var cur_vid_id = newsbulletin.currentBulletin >= 0 ? newsbulletin.bulletinList[newsbulletin.currentBulletin] : null;
    var card = null;
    var col_cls = '';
    var pendingDraft = false;

    // Click function for cards referenced in the for loop, avoid jslint warnings, define it before the loop.
    var cardClickSpecial = function(event) {
      logCall(arguments.callee.name.toString()); // Remove this when finished porting.
      if (event.data.has_transcript) {
        location.href = event.data.transcript_url;
        event.stopPropagation;
        return false;
      } else {
        if (newsbulletin.loggedIn && $('body').hasClass('path-videos')) {
          window.location.href = event.data.vid_matching;
          event.stopPropagation;
          return false;
        } else {
          //Show the video here on this page.
        }
      }
    }

    // Iterate through the entire video list, dividing into rows and columns
    for (var i = fromIndex; i < newsbulletin.bulletinList.length; i++) {
      n_thumb++;

      // Output a new row based on the row template
      row_id++;
      row_num++;
      var vid_row = $(row_template.replace('_ROW_NUM_', row_id));
      $(vid_row).insertBefore('.news-bulletin .row.pagination');
      row = $(vid_row).find('.thumb-row');
      new_row = false;
      if (card) card.addClass('last');

      var pendingTitle = newsbulletin.bulletinList[i].title;

      // Create a thumbnail based on the card template
        // WCAG issue said no alt, but if you want it back, take this: //.replace('_TITLEBULLETIN_', newsbulletin.bulletinList[i].title)
        //.replace('_TITLEBULLETIN_', newsbulletin.bulletinList[i].title)
      card = $(card_template
        .replace('_IMG_SRC_', newsbulletin.bulletinList[i].url)
        .replace('_DATA_BULLETIN_', newsbulletin.bulletinList[i].id)
        .replace('_DATA_INDEX_', i)
        .replace('_INDEX_BULLETIN_', i)
        .replace('_TITLEBULLETIN_', /*newsbulletin.bulletinList[i].title*/'')
        .replace('_DATA_TITLE_', newsbulletin.bulletinList[i].title)
        .replace('_TITLE_', pendingTitle)
        .replace('_DESC_', newsbulletin.bulletinList[i].description)
        .replace('_HAS_TRANSCRIPT_', transcriptLink));

      //AgrisourceSite.showNotBusy();

      n_thumb--; // Update the counters.
      if (new_row) {
        row_num--;
      }
    }

    // Fill any remaining slots with a blank card, otherwise the existing cards will try to occupy the full width
    var rem_slots = (row_num * newsbulletin.nCols) - n_thumb;
    while (rem_slots > 0) {
      card = $('<div class="vid-card blank" />');
      $(row).append(card);
      rem_slots--;
    }

    if (card) card.addClass('last');

    // In single-column mode, we don't use a main viewer and there is no "current video" to worry about
    if (newsbulletin.nCols == 1) {
      return;
    }


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
   * Get a list of news bulletins from rest view.
   */
  function getBulletins(nPages) {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (!nPages) nPages = 1;

    // If all the videos have been retrieved, then nothing more to do... except return
    if (newsbulletin.ytNextPageToken == -1) {
      return;
    }

    // Fetch the videos from youtube using the API
    $.ajax({
      url: '/rest/views/news-bulletin-rest',
      type: 'GET',
      data: {
        'part': 'snippet',
        'playlistId': newsbulletin.lang == 'fr' ? newsbulletin.ytPlaylistFR : newsbulletin.ytPlaylistEN,
        'maxResults': newsbulletin.ytPageSize,
        'pageToken': newsbulletin.ytNextPageToken,
        'key': newsbulletin.googleApiKey
      },
      success: function(response) {
        // Store the nextPageToken
        newsbulletin.ytNextPageToken = response.nextPageToken;
        newsbulletin.ytPagesFetched++;

        // If there is no next page (token is undefined), then hide the button
        if (!newsbulletin.ytNextPageToken) {
          newsbulletin.ytNextPageToken = -1;
          $('.newsbulletin-pager').hide();
        }

        // Initialize an index to be used when searching for historyVid
        var n = newsbulletin.bulletinList.length;
        if (!n) n = 0;

        for (i in response.items) {
          var descrip = response.items[i].snippet.description.replace(/https?:\/\/.*/, '');
          descrip = descrip.replace("\n", "<br>\n");
          newsbulletin.bulletinList.push({
            id: response.items[i].snippet.resourceId.videoId,
            url: response.items[i].snippet.thumbnails.medium.url,
            title: response.items[i].snippet.title,
            processed: 0,
            description: descrip,
          });
          // If historyVid is set and matches the current item, then set the currentBulletin number.
          // Note, this only works on the first page of thumbnails.
          if (newsbulletin.historyVid == response.items[i].snippet.resourceId.videoId) {
            newsbulletin.currentBulletin = n;
            newsbulletin.historyVid = null;
            newsbulletin.scrollToCurrent = true;
            newsbulletin.ytReload = true;
          }
          n++;
        }

        // At this point, either we go back for another page of thumbnails, or display what we have.
        var np = nPages - 1;
        if (np > 0) {
          getBulletins(np);
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
  }
}();
