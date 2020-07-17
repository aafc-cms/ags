(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.newsbulletin = {
    attach: function (context, settings) {
      logCall('Drupal.behaviors.newsbulletin attach');
      if (context === document) {
        logCall('Drupal.behaviors.newsbulletin attach context === document');
        NewsBulletin.init(); // Initialize only when context === document.

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
    ytNextPageToken: null,  // Holds the token to go to the next page of youtube videos
    ytPlaylistEN: null,     // English playlist ID
    ytPlaylistFR: null,     // French playlist ID
    ytPageSize: 24,         // Number of items-per-request from youtube
    ytPagesFetched: 0,      // Number of youtube pages fetched
    ytReload: null,         // Set to true when requesting a reload of the youtube iframe
    lang: 'en',             // language that the content is in
    nCols: 4,               // Number of thumbnail columns
    googleApiKey: '',       // Key for google/youtube API
    bulletinList: [],            // Contains the list of bulletin retrieved thus far from youtube
    currentBulletin: 0,          // The index (zero-based) of the currently-selected bulletin
    historyVid: null,       // May contain the id of a bulletin from the history stack (when the back button is used)
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
    return;
    if (newsbulletin.initialized) {
      return;
    }
    //AgrisourceSite.showBusy();
    initWhenReady();
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

    // Check the history state to see if it contains a video id after using the back button. If so, then we'll tee that one up.
    var ytGetPages = 1;
    if (history.state && history.state.vid) {
      newsbulletin.historyVid = history.state.vid;
      ytGetPages = history.state.npages;
      history.pushState({}, ''); // Otherwise Chrome gets confused
    }

    newsbulletin.lang = $('html').attr('lang');
    newsbulletin.bulletinList = [];

    // If on the main videos page, get/display the initial set of video thumbnails.
    if ($('body').hasClass('newsbulletin')) {
      getBulletins(ytGetPages);
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

    // Add click-handlers to all thumbnail images. The entire card should be clickable.
    $('.news-bulletin .vid-card').click(function() {
      var vid_img = $(this).find('img');
      var vid_card_test = $(vid_img).closest('.vid-card');
      var iPlay = null;
      try {
        iPlay = $(vid_card_test).data('iplay').play;
      } catch (err) {
        // Suppress exception.
      }
      if (typeof iPlay != 'undefined' && iPlay == 0) {
        return;
      }
      play(vid_img);
      // When the video is played, put the focus on video wrapper (accessibility)      
      $('.news-bulletin .embed-responsive-16by9').focus();
    });
    
    $('.news-bulletin .vid-card').keydown(function(e) {
      var key = e.which;
      if (key == 13) { // the enter key code
        /*play($(this).find('img'));
        $('.news-bulletin .embed-responsive-16by9').focus();*/        
        $(this).click();
      }
          
    });

    PMTabs.indexVideo();

    // In single-column mode, we don't use a main viewer and there is no "current video" to worry about
    if (newsbulletin.nCols == 1) {
      return;
    }

    // Reset the main viewer and move it to the row above the currently-selected video
    $('.news-bulletin .player').empty();
    $('.news-bulletin .sidebar').empty();
    $('.news-bulletin .transcript').empty();
    movePlayer();

    // Tee up the current video (if any) but don't start it playing
    if (newsbulletin.currentBulletin >= 0 && newsbulletin.bulletinList[newsbulletin.currentBulletin]) {
      // If the caller has specified a reload of the iframe, then do it after a small delay.
      // This is to overcome a feature in chrome where the iframe does not load after a back-button.
      if (newsbulletin.ytReload) {
        setTimeout(function() {
          var frm = $('.news-bulletin .player iframe');
          var src = $(frm).attr('src');
          $(frm).attr('src', src);
        }, 100);
        newsbulletin.ytReload = false;
      }
      $('.news-bulletin .sidebar').append('<h2>' + newsbulletin.bulletinListt[newsbulletin.currentBulletin].title + '</h2><p>' + newsbulletin.bulletinListt[newsbulletin.currentBulletin].description + '</p>');
      var vid_id = newsbulletin.bulletinListt[newsbulletin.currentBulletin].id;
      var ajax_url = (newsbulletin.lang == 'fr' ? '/fr' : '/en') + '/rest/views/news-bulletin-rest';
      logCall('ajax_url =' + ajax_url);
      $.ajax({
        url: ajax_url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          if (data.no_node_but_has_access) {
            $('.news-bulletin .transcript').empty().append('<a href="' + (newsbulletin.lang == 'fr' ? '/fr' : '/en') + '/videos/vidmatch">' + Drupal.t('Need to match the videos and add a transcript.') + '</a>');
          }
          if (data.transcripts[vid_id]) {
            $('.news-bulletin .transcript').empty().append('<a href="' + data.transcripts[vid_id]['url'] + '">' + Drupal.t('Transcript') + '</a>');

            PMTabs.indexVideo();

          }
        },
        error: function() {
        }
      });
      //this is added to play the first video when we hit enter key on video wrapper
      $('.news-bulletin .embed-responsive-16by9').keydown(function(e) {
        var key = e.which;
        if (key == 13) {
          var elementCard = $(e.target).closest('.vid-card');
          elementCard.trigger('click.cardfirstclick');
          var frmsrc = $('#vframe').attr('src');
          var frmscrRep = frmsrc.replace("autoplay=0", "autoplay=1");
          $('#vframe').attr('src', frmscrRep);    
        }
      });
    }
  }


  /**
   * Get a list of videos from youtube. This will add to the current list and keep track of the current page, so
   * can be called repeatedly.
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
   * Move the main player to be in the row above the currently-selected thumbnail
   */
  function movePlayer(card) {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (!card) {
      card = $('.news-bulletin .vid-card.selected');
    }
    // Determine which bootstrap-row the thumbnail is in, and where the viewer row is
    if (card.length == 0) return; // No thumbnail is selected
    var bs_thumb_row = $(card).closest('.bs-thumb-row');
    var bs_viewer_row = $('.news-bulletin .viewer').closest('.row');
    var viewer_next_row = $(bs_viewer_row).next();
    var viewer_height = $(bs_viewer_row).height();

    // If the viewer is not immediately above the thumbnail row, then move it now (with animation)
    if ($(viewer_next_row).attr('id') != $(bs_thumb_row).attr('id')) {
      $(bs_viewer_row).remove();
      $(bs_viewer_row).insertBefore(bs_thumb_row);
      $(bs_viewer_row).css({'height': 0}).animate({'height': viewer_height}, 500, function() {
        $(bs_viewer_row).css({'height': 'auto'});
      });
    }
  }

  /**
   * Expose functions and variables
   */
  return {
    init: init,
    newsbulletin: newsbulletin,
  }
}();


