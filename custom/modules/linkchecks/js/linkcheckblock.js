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
        //console.log('ALLO!?');
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
    var stopPlayer = 0;

    /**
     * Initialization
     */
    function init() {
      if (initialized) {
        return;
      }
      initialized = true;
  
      // Get the current UI language
      $ = jQuery;
      Linkchecks.lang = $('html').attr('lang');

  
      //var jsonTest = (function () {
        var json = null;
        var getUrl = window.location;
        var baseUrl = getUrl .protocol + "//" + getUrl.host;
        var my_url = baseUrl + '/sites/default/files/views_data_export/broken_links_report_data_export_1/104/links.json';
        var my_url = baseUrl + '/sites/default/files/views_data_export/broken_links_report_data_export_1/1/links.json';
  
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
  
      $(document).on('mousemove', onMouseMove);     

      var copyTextUpperBox = $('</br><span class="glyphicon glyphicon-copy" style="cursor: pointer; color: #007852; font-size: 2em;" title="'+(Linkchecks.lang=='fr'?'Copier le contenu de la boîte en format texte':'Copy the content of the box in text format')+'"></span>');
      $('#links-report').append('</br>');
      $('#links-report').append(copyTextUpperBox);
      $(copyTextUpperBox).click(function() {
        Linkchecks.copyLink($('.upperBox').text());
      });
      var copyHTML = $('<span class="glyphicon glyphicon-copy" style="cursor: pointer; color: Blue; font-size: 2em; padding-left: 20px;" title="'+(Linkchecks.lang=='fr'?'Copier le contenu de la boîte en format HTML':'Copy the content of the box in HTML format')+'"></span>');
      $('#links-report').append(copyHTML);
      $(copyHTML).click(function() {
        Linkchecks.copyLink($('.upperBox').html());
      });
      var copyHTMLBtmBox = $('<span class="glyphicon glyphicon-copy" style="cursor: pointer; color: Blue; font-size: 2em; padding-left: 20px;" title="'+(Linkchecks.lang=='fr'?'Copier le contenu de la boîte en format HTML':'Copy the content of the box in HTML format')+'"></span>');
      $('#request-log').prepend(copyHTMLBtmBox);
      $(copyHTMLBtmBox).click(function() {
        Linkchecks.copyLink($('#request-log').html());
      });
      var copyTextBtmBox = $('<span class="glyphicon glyphicon-copy" style="cursor: pointer; color: #007852; font-size: 2em;" title="'+(Linkchecks.lang=='fr'?'Copier le contenu de la boîte en format texte':'Copy the content of the box in text format')+'"></span>');
      $('#request-log').prepend(copyTextBtmBox);
      $(copyTextBtmBox).click(function() {
        Linkchecks.copyLink($('#request-log').text());
      });
      $('#request-log').prepend('</br>');
      $('#links-report').append('<span style="cursor: pointer; color: red; font-size: 2em; float: right;" id="stop" class="glyphicon glyphicon-stop" title="'+(Linkchecks.lang=='fr'?'Arrêter le défilement':'Stop scrolling')+'"></span>');
      $('#links-report').append('<span style="cursor: pointer; color: green; font-size: 2em; float: right;" id="play" class="glyphicon glyphicon-play" title="'+(Linkchecks.lang=='fr'?'Reprendre le défilement':'Resume scrolling')+'"></span>');
      $('#play').hide();
      $("#stop").click(function() {
        $('#stop').hide();
        $('#play').show();
        stopPlayer = 1;
      });
      $("#play").click(function() {
        $('#play').hide();
        $('#stop').show();
        stopPlayer = 0;
      });
    }

    function animateScroll(speed) {
        $('.upperBox').animate({ scrollTop: $('.upperBox').prop("scrollHeight")}, speed);
    }

    function stopScroll() {
      $('.upperBox').stop();
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
      $('<div class="upperBox" style="padding:15px; background-color: #EFEFEF; max-height: 200px; overflow: auto; border-radius: 10px; border: 1px solid #BBB; font-size: 14px;"></div><br>').insertBefore('#request-log');

      Linkchecks.requestDemo(data);
      //  console.table(Linkchecks.linkReport);
      var finishReport = setInterval(
        function(){
          if (Linkchecks.linksChecked>=Linkchecks.linkReport.length){
            //console.table(Linkchecks.linkReport);
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
      var copied_msg = $('<div>Box content Copied</div>').css({
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
      }, 2000);
    }
  
    function requestDemo(links) {
      if (typeof links === 'undefined') {
        //console.log('call paramedics');
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
      //console.log(Linkchecks.linksChecked);
      //console.log(link);
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
                $('#request-log').append('<a target="_blank" href="'+link.url+'">' + '<span style="color: red;">File ' + line.message + '</span>&nbsp;' + link.linkcheckerlink_page_entity_label + '</a>');
                $('#request-log').append('&nbsp;<a target="_blank" style="color: blue;" href="https://agrisource-jun.9pro.ca/en'+reportLine.element+'">'+(Linkchecks.lang=='fr'?'Cliquez ici pour éditer la page':'View and Edit')+'</a></br>\n');
              }
              else {
                $('#request-log').append('<a target="_blank" href="'+link.url+'">' + '<span style="color: red;">Page ' + line.message + '</span>&nbsp;' + link.linkcheckerlink_page_entity_label + '</a>');
                $('#request-log').append('&nbsp;<a target="_blank" style="color: blue;" href="https://agrisource-jun.9pro.ca/en'+reportLine.element+'">'+(Linkchecks.lang=='fr'?'Cliquez ici pour éditer la page':'View and Edit')+'</a></br>\n');
              }
            }
            //console.table(line.message);
            // dans le block au lieu du console.
            $('.upperBox').append(' - <strong>' + Linkchecks.linksChecked + '</strong> checked ' + ' ' );
            $('.upperBox').append('<span style="color: red;"> ' + reportLine.status + ' ' + reportLine.message + ' </span>' + ' <span style="color: #007852;"> Lang: ' + link.entity_langcode + '</span> ');
            $('.upperBox').append('<a target="_blank" style="color: blue !important;" href=' + reportLine.url + '>' + reportLine.url + '</a>');
            $('.upperBox').append('&nbsp;<a style="color: blue;" target="_blank" href="https://agrisource-jun.9pro.ca/en'+reportLine.element+'">'+(Linkchecks.lang=='fr'?'Afficher et modifier':'View and Edit')+'</a>');
            $('.upperBox').append('</br>\n');
            /*$('.upperBox').animate({ scrollTop: $('.upperBox').prop("scrollHeight")}, 25);*/
            if (stopPlayer === 1 ){stopScroll();}
            else {animateScroll(25);}
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
      animateScroll: animateScroll,
      stopScroll: stopScroll,
      stopPlayer: stopPlayer,
    }
  }();