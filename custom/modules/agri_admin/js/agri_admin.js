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
            '<label for="menu-disabled-links-switch" class="show-disabled"> ' + Drupal.t('Show disabled items') + '</label>' +
            '</form>';
            $('div.region-content .tabledrag-toggle-weight-wrapper').prepend(disabledMenuLinkHtml);
            $('table#menu-overview tr.menu-disabled').each(function(e, index) {
              $(this).hide();
            });
            $('#menu-disabled-links-switch label.hide-disabled').hide();
            $("#menu-disabled-links-switch").click(function(e) {
              $('table#menu-overview tr.menu-disabled').each(function(e, index) {
                if ($(this).is(":hidden")) {
                  $(this).show();
                }
                else {
                  $(this).hide();
                }
              });
            });
	        }

        }//end if user is loggedIn
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
