(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.newsmisc = {
    attach: function (context, settings) {
      logCall('Drupal.behaviors.newsmisc attach');
      if (context === document) {
        logCall('Drupal.behaviors.newsmisc attach context === document');
        NewsMisc.initWhenReady(); // Initialize only when context === document.
      } else {
        // Functions that need to be called on other attach calls (ex: by ajax) should be added here.
      }
    }
  };
})(jQuery, Drupal, drupalSettings);

/**
 * Usage logCall(arguments.callee.name.toString()); // Logs call of current function.
 **/
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
 * Class to handle the video page.
 */
var NewsMisc = function() {
  var newsmisc = {
    initialized: false,  // Flag to indicate that this class has been initialized
    lang: 'en',          // language that the content is in
    ready: false,        // iFrame is ready.
    resized: false,      // twitter iFrame has been resized.
    retryCount: 0,      // twitter iFrame has been resized.
    page: '',            // Name of the page we're processing.
  };

  /**
   * Initialization (one time)
   */
  function init() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (newsmisc.initialized) {
      return;
    }
    newsmisc.lang = $('html').attr('lang');
    if (newsmisc.page == 'transcript') {
      iResizeWhenReady();
    }
    // TODO: figure out why autoplay isn't working here.
    if(1) {
      var frmsrc = $('#vframe').attr('src');
      var frmscrRep = frmsrc.replace("autoplay=0", "autoplay=1");
      $('#vframe').attr('src', frmscrRep);
    }
  }


  function iResizeWhenReady() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (!newsmisc.ready) {
      setTimeout(function() { iResizeWhenReady(); }, 100);
    }
    if (newsmisc.ready && !newsmisc.resized) {
      var twitterIframe = $('.region-sidebar-second iframe');
      if (typeof twitterIframe !== 'undefined' && twitterIframe) {
        setTimeout(function() {
          var height = $('.row.content-inner section').height();
          var twitterBannerHeight = $('#block-twitterimageblock').height();
          var fluff = 15;
          var twitterHeight = $('.region-sidebar-second iframe').height() + twitterBannerHeight + fluff;
          if (twitterHeight > height|| newsmisc.retryCount > 100) {
            newsmisc.resized = true;
	  } else {
            PMTwitter.iResize(height);
            newsmisc.retryCount++;
            iResizeWhenReady();
          }
        }, 100);
      } else {
        setTimeout(function() { iResizeWhenReady(); }, 100);
      }
    }
  }


  /**
   * Initialization phase 2. Waits for the googleApiKey to be set and then continues initialization.
   */
  function initWhenReady() {
    logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if ($('body').hasClass('page-node-type-video')) {
      newsmisc.page = 'transcript';
    }
    if (newsmisc.page == 'transcript') {
      var twitterIframe = $('.region-sidebar-second iframe');
      if (typeof twitterIframe !== 'undefined' && twitterIframe) {
        newsmisc.ready = true;
      }
    }

    if (newsmisc.page != 'transcript') {
      // Done pre-init conditions.
      newsmisc.ready = true;
    }
    if (!newsmisc.ready) {
      logCall('initWhenReady call again');
      setTimeout(function() { initWhenReady(); }, 100);
      return;
    }
    init();
  }

  /**
   * Expose functions and variables
   */
  return {
    init: init,
    initWhenReady: initWhenReady,
    newsmisc: newsmisc
  }
}();


