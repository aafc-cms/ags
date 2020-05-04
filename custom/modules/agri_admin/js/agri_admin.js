/**
 * @file
 * Agri Admin behaviors.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.agriAdmin = {
    attach: function (context, settings) {
      if (context == document) {
        if ($('body').hasClass('nodeaddempl') || $('body').hasClass('nodeaddnews')) {
          $('body').removeClass('etuf-sbs');
        }
        if ($('body').hasClass('node-edit') && $('body').hasClass('page-node-type-empl')) {
          $('body').removeClass('etuf-sbs');
        }
        if ($('body').hasClass('node-edit') && $('body').hasClass('page-node-type-news')) {
          $('body').removeClass('etuf-sbs');
        }
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

          //ETUF - sync the news type selection, with JS
          var bothSelects = $("#edit-layout-selection, #edit-layout-selection-etuf-fr, #edit-layout-selection-etuf-en");
          bothSelects.change(function(e) {
            bothSelects.val(this.value); // "this" is the changed one
          });

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
          }

        }//end if user is loggedIn
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
