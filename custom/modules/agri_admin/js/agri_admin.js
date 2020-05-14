/**
 * @file
 * Agri Admin behaviors.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.agriAdmin = {
    attach: function (context, settings) {
      if (context == document) {
        Agrisource.init();
        if ($('body').hasClass('user-logged-in')) {
          // Check if the actual 'admin' user is logged in, based on the name displayed in the toolbar.
          // There is a small delay before this information is available, so set a timer.
          // If this needs to be based on the admin _role_ instead, then we'll need an API function for that.
          setTimeout(function() {
            var user = $('#toolbar-item-user').text();
            if (user != 'admin') {
              //$('#edit-field-meta-tags-0').hide(); // Hide the META TAGS tab in the node editor
              //$('#edit-field-meta-tags-etuf-fr-0').hide(); //because now weh have the ETUF (fr and en form)
              //$('#edit-author').hide(); //Hide the Authoring Information.
              //$('#edit-meta-author').hide();
              //$('#edit-revision-information').hide();
              //$('#edit-content-translation').hide();
              $('.js-form-item-promote-value.form-item-promote-value').hide();
              $('.js-form-item-promote-etuf-fr-value.form-item-promote-etuf-fr-value').hide();
              console.log('agri_admin hide the promote checkbox on node edit.');
            }
          }, 500);

          //make sure in ETUF the french language is french (it defaults to english if creating node in french UI)
          if ($('html').attr('lang') == 'fr') {
            $('#edit-langcode-0-value option[value="en"]').removeAttr("selected");
            $('#edit-langcode-0-value option[value="fr"]').attr("selected","selected");
          }


          //ETUF - sync the publish status selection, with JS
          var bothStatus = $("#edit-moderation-state-0-state, #edit-moderation-state-etuf-fr-0-state, #edit-moderation-state-etuf-en-0-state");
          bothStatus.change(function(e) {
            bothStatus.val(this.value); // "this" is the changed one
          });

          //change the submit button value to "Save" instead of "Save (this translation)"
          if ($('body').hasClass('i18n-en')) {
            $(".node-form .form-actions #edit-submit").val("Save");
          }

          // Hide disabled menu links
          if ($('body').hasClass('adminstructuremenumanagesideb') || $('body').hasClass('adminstructuremenumanagemain')) {
            var disabledMenuLinkHtml = '<form id="menu-disabled-links-form" action="#nothing">' +
            '<input type="checkbox" id="menu-disabled-links-switch" name="menu-disabled-links-switch" value="enabled" checked>' +
            '<label for="menu-disabled-links-switch" class="show-disabled"> ' + Drupal.t('Hide disabled items') + '</label>' +
            '</form>';
            $('div.region-content .tabledrag-toggle-weight-wrapper').prepend(disabledMenuLinkHtml);
            $('table#menu-overview tr.menu-disabled').each(function(e, index) {
              $(this).hide();
            });
            $('#menu-disabled-links-switch label.hide-disabled').hide();
            $("#menu-disabled-links-switch").click(function(e) {
              $('table#menu-overview tr.menu-disabled').each(function(index, element) {
                if ($(element).is(":hidden")) {
                  $(element).show();
                }
                else {
                  $(element).hide();
                }
              });
            });
          }

          // Hide disabled menu links in the node edit form
          if ($('body').hasClass('path-node') && $('body').hasClass('user-logged-in')) {
            $("#edit-menu-enabled").click(function(e) {
              var is_checked = $(this).is(':checked');
              if (is_checked) {
                $("#menu-disabled-links-form").show();
              }
              else {
                $("#menu-disabled-links-form").hide();
              }
            });
            var disabledMenuLinkHtml = '<form id="menu-disabled-links-form" action="#nothing">' +
            '<input type="checkbox" class="form-boolean--type-checkbox form-checkbox form-boolean" id="menu-disabled-links-switch" name="menu-disabled-links-switch" value="enabled" checked>' +
            '<label for="menu-disabled-links-switch" class="show-disabled form-item__label"> ' + Drupal.t('Hide disabled items') + '</label>' +
            '</form>';
            $('#edit-menu div.form-type--checkbox').prepend(disabledMenuLinkHtml);
            $('#edit-menu option').each(function(index, element) {
              if (~$(element).text().indexOf('disabled)')) {
                $(element).hide();
              }
            });
            var is_expanded = $("#edit-menu summary").attr('aria-expanded');
            if (is_expanded) {
              $("#menu-disabled-links-form").hide();
            }
            else {
              $("#menu-disabled-links-form").show();
            }
            $("#menu-disabled-links-switch").click(function(e) {
              $('select.menu-parent-select').first().find('option').each(function(index, element) {
                if ($(element).text().indexOf('isabled)') > 0) {
                  var is_hidden = $(element).css('display') == 'none';
                  if (is_hidden) {
                    $(element).show();
                  }
                  else {
                    $(element).hide();
                  }
                }
              });
            });
            if (!$("#edit-menu-enabled").is(":checked") && $("#edit-menu-enabled").is(':visible')) {
              var is_hidden = $("#menu-disabled-links-form").css('display') == 'none';
              if (is_hidden) {
                $("#menu-disabled-links-form").show();
              }
              else {
                $("#menu-disabled-links-form").hide();
              }
            } else {
              var is_hidden = $("#menu-disabled-links-form").css('display') == 'none';
              if (is_hidden) {
                $("#menu-disabled-links-form").show();
              }
              else {
                $("#menu-disabled-links-form").hide();
              }
            }
          }

        }//end if user is loggedIn
      }
    }
  };
})(jQuery, Drupal, drupalSettings);


var Agrisource = function() {
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
    lang = $('html').attr('lang');

    //Determine the page type
    if ($('body').hasClass('path-frontpage')) {
      Agrisource.page_type = 'front'; // Front page <front> ex, /en or /fr.
    } else if ($('body').hasClass('nodeaddpage')) {
      Agrisource.page_type = 'add-page';
    }
    else if ($('body').hasClass('nodeaddlanding_page')) {
      Agrisource.page_type = 'add-landing-page';
    }
    else if ($('body').hasClass('nodeaddempl')) {
      Agrisource.page_type = 'add-empl';
    }
    else if ($('body').hasClass('nodeaddnews')) {
      Agrisource.page_type = 'add-news';
    }
    else if ($('body').hasClass('node-edit') && $('body').hasClass('page-node-type-landing-page')) {
      Agrisource.page_type = 'edit-landing-page';
    }
    else if ($('body').hasClass('node-edit') && $('body').hasClass('page-node-type-page')) {
      Agrisource.page_type = 'edit-page';
    }
    else if ($('body').hasClass('node-edit') && $('body').hasClass('page-node-type-news')) {
      Agrisource.page_type = 'edit-news';
    }
    else if ($('body').hasClass('node-edit') && $('body').hasClass('page-node-type-empl')) {
      Agrisource.page_type = 'edit-empl';
    }
    else if ($('body').hasClass('path-webform')) {
      Agrisource.page_type = 'webforms';
    }
    else if ($('body').hasClass('grstheme-bootstrap') && $('body').hasClass('path-node')) {
      Agrisource.page_type = 'content'; // Content pages generated by grstheme_bootstrap.
    }
    else if ($('body').hasClass('path-admin')) {
      Agrisource.page_type = 'admin'; // Other admin page.
    }

    if (Agrisource.page_type == 'edit-page' ||
        Agrisource.page_type == 'add-page' ||
        Agrisource.page_type == 'add-landing-page' ||
        Agrisource.page_type == 'edit-landing-page'
    ) {
      setLayoutDefaults();
    }
    $(document).on('mousemove', onMouseMove);

    //remove the data-toggle from the links anchors in main navigation for media or large devices

    $('.alert a.access-unpublished').each(function() {
      if ($(this).hasClass('access-unpublished')) {
        // Add a copy-link icon to the unpublished link, if one exists
        var access_link = $('a.access-unpublished');
        if (access_link.length > 0) {
          var spn = $('<span class="glyphicon glyphicon-copy" style="margin-left: 10px" title="'+(lang=='fr'?'Copier le lien':'Copy link')+'"></span>');
          $(access_link).parent().append(spn);
          $(spn).click(function() {
            Agrisource.copyLink(window.location.origin + $(access_link).attr('href'));
          });
        }
      }
    });

    initialized = true;
  }

  /**
   * Keep track of mouse movements.
   */
  function setLayoutDefaults() {
    // Set the layout defaults.
    var layoutElement = $("#edit-layout-selection");
    if (Agrisource.page_type == 'add-page') {
      layoutElement.val('node_page_default_default'); // Default to the default layout value.
    }
    if (Agrisource.page_type == 'add-landing-page') {
      layoutElement.val('node_landing_page_full_default'); // Default to the default layout value.
    }

    if (Agrisource.page_type == 'edit-landing-page') {
      if (layoutElement.val() == '_none') {
        layoutElement.val('node_landing_page_full_default'); // Default to the default layout value.
      }
    }
    if (Agrisource.page_type == 'edit-page') {
      if (layoutElement.val() == '_none') {
        layoutElement.val('node_page_default_default'); // Default to the default layout value.
      }
    }
    //Layout - sync the layout type selection, with JS
    var bothSelects = $("#edit-layout-selection, #edit-layout-selection-etuf-fr, #edit-layout-selection-etuf-en");
    bothSelects.change(function(e) {
      bothSelects.val(this.value); // "this" is the changed one
    });

  }

  /**
   * Keep track of mouse movements.
   */
  function onMouseMove(event) {
    Agrisource.mouse.x = event.clientX;
    Agrisource.mouse.y = event.clientY;
  }

  /**
   * Copy a link to the clipboard
   */
  function copyLink(url) {
    if (isIE()) {
      window.clipboardData.setData('Text', url);
    }
    else {
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
    }

    // Display a popup message to indicate the link was copied, then fade it out
    lang = Agrisource.lang;
    var copied_msg = $('<div>'+(lang=='fr'?'Copié':'Copied')+'</div>').css({
      'position': 'fixed',
      'background-color': 'black',
      'border-radius': '6px',
      'color': 'white',
      'padding': '10px 15px',
      'top': Agrisource.mouse.y,
      'left': Agrisource.mouse.x,
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
    copyLink: copyLink,
  }
}();

