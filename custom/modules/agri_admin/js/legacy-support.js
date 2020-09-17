/**
 * @file
 * Agri Admin behaviors.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.legacySupport = {
    attach: function (context, settings) {
      if (context == document) {
        Legacysupport.init();
      }
    }
  };
})(jQuery, Drupal, drupalSettings);


var Legacysupport = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var mouse = {x:0, y:0};    // Tracks the mouse position
  var page_type = 'content';

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      return;
    }

    // Get the current UI language
    $ = jQuery;
    Legacysupport.lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('path-admin')) {
      Legacysupport.page_type = 'admin'; // Other admin page.
    }

    if (Legacysupport.page_type == 'admin') {
      $(document).on('mousemove', onMouseMove);

      //remove the data-toggle from the links anchors in main navigation for media or large devices

      $('table tr td.views-field-nid').each(function(index, el) {
        if (jQuery(el).length > 0) {
          if ($(el).find('span').first().hasClass('nid')) {
            // Add a copy-link icon to the unpublished link, if one exists
            $(el).find('.fa-clipboard').click(function() {
              Legacysupport.copyDcrId($(this).parent().find('.legacy-dcr-id').text());
            });
          }
        }
      });
    }

    initialized = true;
  }


  /**
   * Keep track of mouse movements.
   */
  function onMouseMove(event) {
    Legacysupport.mouse.x = event.clientX;
    Legacysupport.mouse.y = event.clientY;
  }

  /**
   * Copy a link to the clipboard
   */
  function copyDcrId(dcrid) {
    if (isIE()) {
      window.clipboardData.setData('Text', dcrid);
    }
    else {
      var range = document.createRange();
      var tmpElem = $('<div>');
      tmpElem.text(dcrid);
      $('body').append(tmpElem);
      range.selectNodeContents(tmpElem.get(0));
      selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      document.execCommand('copy', false, null);
      tmpElem.remove();
    }

    // Display a popup message to indicate the link was copied, then fade it out
    lang = Legacysupport.lang;
    var copied_msg = $('<div>'+(lang=='fr'?'Copié':'Copied')+'</div>').css({
      'position': 'fixed',
      'background-color': 'black',
      'border-radius': '6px',
      'color': 'white',
      'padding': '10px 15px',
      'top': Legacysupport.mouse.y,
      'left': Legacysupport.mouse.x,
      'z-index': 10700
    })
    $('body').append(copied_msg);
    setTimeout(function() {
      $(copied_msg).animate({'opacity': 0}, 1000, function() {
        $(this).remove();
      });
    }, 1000);
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
    mouse: mouse,
    page_type: page_type,
    copyDcrId: copyDcrId,
  }
}();

