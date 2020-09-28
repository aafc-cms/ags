/**
 * @file
 * legacySupport behaviors.
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
  var data = [];
  var some_id = null;
  var nids = [];

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

    jQuery('form.views-exposed-form .form-item--nid input#edit-nid').keyup(function() {
      processIdInput(this);
    });
    jQuery('form.views-exposed-form .form-item--nid input#edit-nid').blur(function() {
      processIdInput(this);
    });

    var inputIdElement = jQuery('form.views-exposed-form input#edit-nid');
    processIdInput(inputIdElement); // Initialize.
    initialized = true;
  }


  function processIdInput(temp_el) {
    var nid = $(temp_el).val();
    if ($(temp_el).val().length > 0) {
      nid = Number(nid);
      getDcrId(nid);  
    }
  }

  /**
   * logCall().
   **/
  function logCall(funcName, force) {
    if (typeof data[funcName] == 'undefined') {
      Legacysupport.data[funcName] = 0;
    }
    if (typeof force == 'undefined') {
      force = false;
    }
    Legacysupport.data[funcName]++;
    var debug = true; // Debug is disabled.
    if (debug || force) {
      console.log(funcName + ' call:' + Legacysupport.data[funcName]);
    }
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
   * Convert nid into dcr_id.
   */
  function getDcrId(id) {
    Legacysupport.nids = [id];
    jQuery('html, body').css("cursor", "wait");
    if (Drupal.theme.ajaxProgressIndicatorFullscreen) {
      if (!jQuery('.ajax-progress').length && !jQuery('.ajax-progress-throbber').length) {
        jQuery('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen(Drupal.t('Processing nid and retrieving dcr_id.')));
      }
    }
    else {
      console.log('Where is Drupal.theme.ajaxProgressIndicatorFullscreen ?');
    }

    Legacysupport.logCall(arguments.callee.name.toString()); // Remove this when finished porting.

    var nidsParam = '';
    if (Legacysupport.nids.length) {
      nidsParam = '?nids=' + Legacysupport.nids.join();
    }
    //  This ajax option works very well instead of ?= and &= if order isn't important.  However if order is important you can use .join instead.
    // Optionally make the agax call using data.
    /* data: {
        'nids': Legacysupport.nids
    }*/

    jQuery.ajax({
      url: '/' + Legacysupport.lang + '/admin/legacydcr' + nidsParam,
      type: 'GET',
      success: function(response) {
        console.log(response);
        if (typeof response.message !== 'undefined') {
          if (typeof response.message[0] !== 'undefined') {
            var result = response.message[0];
            if (result.toString().length == 13) {
              createCopyButton(result); 
            }
            else if (result.toString().length > 1) {
              createCopyButton(result); 
            }
            else {
              removeCopyWidget(); 
            }
          }
          else {
            removeCopyWidget(); 
          }
        }
        else {
          removeCopyWidget(); 
        }
        // Disable the ajax throbber / spinner for show busy.
        jQuery('html, body').css("cursor", "auto");
        jQuery('div.ajax-progress').each(function(index, element) {
          $(element).remove(); // Remove the throbber.
        });
        jQuery('div.ajax-progress').each(function(index, element) {
          $(element).remove(); // Remove the throbber.
        });
        jQuery('input').each(function(index,element) {
          jQuery(element).remove(".ajax-progress-throbber"); // Remove the throbber like this.
        });
      },
      error: function(message) {
        Legacysupport.removeCopyWidget();
        console.log('ajax error');
        jQuery('html, body').css("cursor", "auto");
        jQuery('div.ajax-progress').each(function(index, element) {
          $(element).remove(); // Remove the throbber.
        });
        jQuery('div.ajax-progress').each(function(index, element) {
          $(element).remove(); // Remove the throbber.
        });
        jQuery('input').each(function(index,element) {
          jQuery(element).remove(".ajax-progress-throbber"); // Remove the throbber like this.
        });
        if (Drupal.theme.ajaxProgressMessage) {
          jQuery('table.table').after(Drupal.theme.ajaxProgressMessage(Drupal.t('Error with ajax call in getDcrId().')));
        }
        else {
          console.log('Where is Drupal.theme.ajaxProgressMessage()?')
        }
      }
    });
  }


  function removeCopyWidget() {
    Legacysupport.logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    var nidFontAwesomeSpan = jQuery('form.views-exposed-form .form-item--nid span.fa.fa-clipboard');
    if (typeof nidFontAwesomeSpan !== 'undefined') {
      $(nidFontAwesomeSpan).remove();
      var nidLegacyDcrSpan = jQuery('form.views-exposed-form span.legacy-dcrid');
      $(nidLegacyDcrSpan).remove();
    }
    jQuery('form.views-exposed-form .form-item--nid label').text('ID');
  }


  function createCopyButton(raw_id) {
    Legacysupport.logCall(arguments.callee.name.toString()); // Remove this when finished porting.
    if (typeof raw_id == 'undefined') {
      return;
    }
    Legacysupport.some_id = null;
    removeCopyWidget();

    var identifier = Number(raw_id);
    if (identifier > 0) {
      if (identifier.toString().length < 13) {
        jQuery('form.views-exposed-form .form-item--nid label').text('(ID)DCRID');
      }
      else {
        jQuery('form.views-exposed-form .form-item--nid label').text('ID(DCRID)');
      }
      Legacysupport.some_id = identifier;
      var spn = $('<span class="legacy-dcrid or-some-id">' + Legacysupport.some_id + '</span><span class="dcrid fa fa-clipboard" style="margin-left: 10px" title="'+(Legacysupport.lang=='fr'?'Copier le lien':'Copy link')+'"></span>');
       
      $('input#edit-nid').parent().append(spn);
      $('input#edit-nid').parent().find('.fa-clipboard').click(function() {
        Legacysupport.copyDcrId($(this).parent().find('.legacy-dcrid').text());
      });
      if (Legacysupport.some_id.toString().length < 13
       && Legacysupport.some_id.toString().length > 1) {
        jQuery('input#edit-nid').val(Legacysupport.some_id);
      }
    }
    else {
      jQuery('form.views-exposed-form .form-item--nid label').text('ID');
    }
  }


  /**
   * Expose functions and variables
   */
  return {
    init: init,
    lang: lang,
    data: data,
    nids: nids,
    logCall: logCall,
    mouse: mouse,
    page_type: page_type,
    some_id: some_id,
    copyDcrId: copyDcrId,
    removeCopyWidget: removeCopyWidget,
  }
}();

