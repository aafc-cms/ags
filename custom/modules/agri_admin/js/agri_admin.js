(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.agri_admin = {
    attach: function (context, settings) {
      if (context == document) {
        if ($('body').hasClass('user-logged-in')) {
          // Check if the actual 'admin' user is logged in, based on the name displayed in the toolbar.
          // There is a small delay before this information is available, so set a timer.
          // If this needs to be based on the admin _role_ instead, then we'll need an API function for that.
          setTimeout(function() {
            var user = $('#toolbar-item-user').text();
            if (user != 'admin') {
              $('#edit-field-meta-tags-0').hide(); // Hide the META TAGS tab in the node editor
              $('#edit-field-meta-tags-etuf-fr-0').hide(); //because now weh have the ETUF (fr and en form)
              $('#edit-author').hide(); //Hide the Authoring Information requested by COMMS (#issue:57)
              $('#edit-meta-author').hide();              
              $('#edit-revision-information').hide();
              $('#edit-content-translation').hide();
              $('.js-form-item-promote-value.form-item-promote-value').hide();
              $('.js-form-item-promote-etuf-fr-value.form-item-promote-etuf-fr-value').hide();
            }
          }, 500);
  

          // When editing any type of node, display a confirmation dialog any time the state is changing from
          // non-published to published.
          if ($('body').hasClass('path-node')) {
        	  
        	  //Commenting this out for now because for some reason, submit function is running twice regardless
        	  /*
            $('#edit-submit').click(function(e) {
              // Get the current state. Need to clone this object and remove the label so that we can get just the state.
              var cur_state = '';
              var mod_state = $('#edit-moderation-state-0-current').clone();
              if (mod_state) {
                $('label', mod_state).remove();
                var cur_state = $(mod_state).text().trim();
              }
              var new_state = $('#edit-moderation-state-0-state option:selected').text();
              // If changing from un-published to published...
              if ((cur_state == '' || cur_state == 'Draft') && (new_state == 'Published')) {
                if (! confirm('Are you sure you want to publish this item?')) {
                  e.preventDefault();
                  return false;
                }
              }
              return true;
            });
            $('#edit-create-and-translate').click(function(e) {
              // Get the current state. Need to clone this object and remove the label so that we can get just the state.
              var cur_state = '';
              var mod_state = $('#edit-moderation-state-0-current').clone();
              if (mod_state) {
                $('label', mod_state).remove();
                var cur_state = $(mod_state).text().trim();
              }
              var new_state = $('#edit-moderation-state-0-state option:selected').text();
              // If changing from un-published to published...
              if ((cur_state == '' || cur_state == 'Draft') && (new_state == 'Published')) {
                if (! confirm('Are you sure you want to publish this item?')) {
                  e.preventDefault();
                  return false;
                }
              }
              return true;
            });
            */
            // The sticky will be automatically be pinned if the users select itinerary or news release as news Type
            $('.js-form-item-sticky-value input[type="checkbox"]').click(function(event) {
            	if (!$(this).prop('checked')) {
            		$(this).prop('checked', false);            		
            	} 
            	else {
            		$(this).prop('checked', true);            		
        		}            	
            });
            
            $('.node-article-form #edit-field-news-category, .node-article-edit-form #edit-field-news-category').click(function(event) {
            	var sticky_chk = '.js-form-item-sticky-value input[type="checkbox"]';
            	if ($(this).val() == "1" || $(this).val() == "3") {
            		if (!$(sticky_chk).prop('checked')) {
            			$(sticky_chk).prop('checked', true);
            		}
            	}
            	else {
            		$(sticky_chk).prop('checked', false);
            	}
            });            
          }
     

          // When editing a French translation of a Photo Gallery node, provide a default value for
          // the photo-by field if it's either empty or set to the default English string
          if ($('html').attr('lang') == 'fr') {
            if ($('body').hasClass('page-node-type-photo-gallery')) {
              var txt = $('#edit-field-photos-by-0-value').val();
              if (typeof(txt) != "undefined") {
                if (txt == "" || txt.toLowerCase() == 'photo by adam scotti (pmo)') {
                  $('#edit-field-photos-by-0-value').val('Photo par Adam Scotti (CPM)');
                }
              }
            }
          }
          if ($('form').hasClass('node-photo-gallery-form')) {	          
	          //must also account for ETUF (side-by-side PG node edit form)
	          var etufTxt = $("#edit-field-photos-by-etuf-fr-0-value").val();   
	          if (typeof(etufTxt) != "undefined") {
	              if (etufTxt == "" || etufTxt.toLowerCase() == 'photo by adam scotti (pmo)') {
	                $('#edit-field-photos-by-etuf-fr-0-value').val('Photo par Adam Scotti (CPM)');
	              }
	          }
	      }
          
          //make sure in ETUF the french language is french (it defaults to english if creating node in french UI)
          if ($('html').attr('lang') == 'fr') {
        	  $('#edit-langcode-0-value option[value="en"]').removeAttr("selected");
              $('#edit-langcode-0-value option[value="fr"]').attr("selected","selected");
          }         
          
          //ETUF - sync the news type selection, with JS
          var bothSelects = $("#edit-field-news-category, #edit-field-news-category-etuf-fr, #edit-field-news-category-etuf-en");
          bothSelects.change(function(e) {
        	  bothSelects.val(this.value); // "this" is the changed one
		  }); 
          
          //ETUF - sync the publish status selection, with JS
         
          var bothStatus = $("#edit-moderation-state-0-state, #edit-moderation-state-etuf-fr-0-state, #edit-moderation-state-etuf-en-0-state");
          bothStatus.change(function(e) {
        	  bothStatus.val(this.value); // "this" is the changed one
		  }); 
          
          //ETUF - sync order for minister / parl sec / ministry
          var bothOrders = $("#edit-field-order-0-value, #edit-field-order-etuf-fr-0-value, #edit-field-order-etuf-en-0-value");
          bothOrders.change(function(e) {
        	  bothOrders.val(this.value); // "this" is the changed one
		  }); 
          
          //change the submit button value to "Save" instead of "Save (this translation)"
          //$(".node-form .form-actions #edit-submit").val("Save");
          
          
          
        }//end if user is loggedIn
      }
    }
  };
})(jQuery, Drupal, drupalSettings);

