/**
 * @file
 * Linkchecks behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.linkchecks = {
    attach: function (context, settings) {
      Linkchecks.init();
    }
  };

} (jQuery, Drupal));

var globalJsonValues = {};

var Linkchecks = function() {
  var initialized = false;   // Flag to indicate that this class has been initialized
  var linkReport = [];
  var linksChecked = -1;
  var form_required_valid = true; // Flag to indicate that the validation is for the News and EO forms
  var lang = 'en';           // Will be 'en' or 'fr' regardless of how the url segment is formed (currently eng or fra)
  var link = null;
  var links = null;
  var mouse = {x:0, y:0};    // Tracks the mouse position
  var page_type = 'content';
  var json = null;

  /**
   * Initialization
   */
  function init() {
    if (initialized) {
      $('form.views-exposed-form select.form-select').each(function(index, el) {
        $(el).attr('disabled', 'disabled');
      });
      return;
    }
    initialized = true;

    // Get the current UI language
    $ = jQuery;
    Linkchecks.lang = $('html').attr('lang');
    $('form.views-exposed-form select.form-select').each(function(index, el) {
      $(el).attr('disabled', 'disabled');
    });
    $('form.views-exposed-form *').each(function(index, el) {
      $(el).attr('disabled', 'disabled');
    });
    $('form.views-exposed-form button').attr('disabled', 'disabled');


    //var jsonTest = (function () {
      var json = null;
      var getUrl = window.location;
      var baseUrl = getUrl .protocol + "//" + getUrl.host;
      var my_url = baseUrl + '/sites/default/files/views_data_export/broken_links_report_data_export_1/104/links.json';


      $.ajax({
        'async': false,
        'global': false,
        'url': my_url,
        'dataType': "json",
        'success': function success(data) {
          json = data;
          var globalJsonValues = json;
          Linkchecks.linkCheckingLoops(json);
        }
      });
    //  return json;
    //})(); 

    $(document).on('mousemove', onMouseMove);

  }


  /**
   * Keep track of mouse movements.
   */
  function onMouseMove(event) {
    Linkchecks.mouse.x = event.clientX;
    Linkchecks.mouse.y = event.clientY;
  }


  function linkCheckingLoops(data) {
    if (typeof data === 'undefined') {
      data = globalJsonValues;
    }
    var linkReport = [];
    $('<div id="links-report"></div>').insertBefore('form.views-exposed-form');
    $('<div id="request-log"></div>').insertBefore('form.views-exposed-form');
    Linkchecks.requestDemo(data);
    //  console.table(Linkchecks.linkReport);
    var finishReport = setInterval(
      function(){
        if (Linkchecks.linksChecked>=Linkchecks.linkReport.length){
          console.table(Linkchecks.linkReport);
          clearInterval(finishReport);
        }
      }
    ,3000);
  }

  /**
   * Copy a link to the clipboard
   */
  function copyLink(url) {
    var range = document.createRange();
    var tmpElem = $('<div>');
    tmpElem.text(url);
    $('body').append(tmpElem);
    range.selectNodeContents(tmpElem.get(0));
    selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
    document.execCommand('copy', false, null);
    tmpElem.remove();

    // Display a popup message to indicate the link was copied, then fade it out
    lang = Linkchecks.lang;
    var copied_msg = $('<div>'+(lang=='fr'?'Copié':'Copied')+'</div>').css({
      'position': 'fixed',
      'background-color': 'black',
      'border-radius': '6px',
      'color': 'white',
      'padding': '10px 15px',
      'top': Linkchecks.mouse.y,
      'left': Linkchecks.mouse.x,
      'z-index': 10700
    })
    $('body').append(copied_msg);
    setTimeout(function() {
      $(copied_msg).animate({'opacity': 0}, 1000, function() {
        $(this).remove();
      });
    }, 1000);
  }

  function requestDemo(links) {
    if (typeof links === 'undefined') {
      console.log('call paramedics');
    }
    Linkchecks.links = links;
    Linkchecks.json = globalJsonValues;
    //Linkchecks.links = links;
    if (Linkchecks.linksChecked == -1) {
      Linkchecks.linksChecked = 0;
    }
    if (Linkchecks.links.length > Linkchecks.linksChecked) {
      Linkchecks.link = Linkchecks.links[Linkchecks.linksChecked];
    }
    var link = Linkchecks.link;
    console.log(Linkchecks.linksChecked);
    console.log(link);
    var http = new XMLHttpRequest();
    var reportLine = {url: link.url, status:0, message : "", element : '/node/' + link.entity_id__target_type};

    http.open('HEAD', reportLine.url);
    linkReport.push(reportLine);
      
    http.onreadystatechange = (function(line,xhttp) {
      return function(){
        if (xhttp.readyState == xhttp.DONE) {
          line.status = xhttp.status;
          Linkchecks.linksChecked++;
          line.message = xhttp.responseText + xhttp.statusText;
          var tempText = $('#request-log').text();
          if (line.message != 'OK') {
            if (link.entity_id__target_type == 101) {
              $('#request-log').append('<a href="'+link.url+'">' + '<span style="color: red;">File ' + line.message + '</span> ' + link.linkcheckerlink_page_entity_label + "</a></br>\n");
            }
            else {
              $('#request-log').append('<a href="'+link.url+'">' + '<span style="color: red;">Page ' + line.message + '</span> ' + link.linkcheckerlink_page_entity_label + "</a></br>\n");
            }
            //$('#request-log').text(tempText + "\n" + line.message);
          }
          console.table(line.message);
          //console.table(xhttp);
          Linkchecks.requestDemo(Linkchecks.links);
        }
      }
    })(reportLine, http);
    http.send();
  }

  /**
   * Expose functions and variables
   */
  return {
    init: init,
    copyLink: copyLink,
    lang: lang,
    link: link,
    links: links,
    linkReport: linkReport,
    linksChecked: linksChecked,
    linkCheckingLoops: linkCheckingLoops,
    requestDemo: requestDemo,
    json: json,
    mouse: mouse,
  }
}();

