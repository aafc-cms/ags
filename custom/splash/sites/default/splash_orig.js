$ = jQuery;

$(document).ready(function() {
  $(window).resize(function() {
    set_sizes();
  });
  $('body').css('background-position', 'center');
  setTimeout(function() { // Need a small delay for the first positioning to be right
    set_sizes();
  }, 100);
  $(document).keyup(function(event) {
    if (event.keyCode == 13) { // Enter
      $('input[name=bk]').parent().submit();
    }
  });
});
function set_sizes() {
  var wh = $(window).height();
  var ww = $(window).width();
  var w_aspect = ww / wh;

  $('.css-maph').each(function() {
    var attr = $(this).data('attr');
    var d = $(this).data('map').split(',');
    $(this).css(attr, sc_calc(wh, parseInt(d[0]), parseInt(d[1]), parseInt(d[2]), parseInt(d[3])));
  });

  $('.css-mapw').each(function() {
    var attr = $(this).data('attr');
    var d = $(this).data('map').split(',');
    $(this).css(attr, sc_calc(ww, parseInt(d[0]), parseInt(d[1]), parseInt(d[2]), parseInt(d[3])));
  });

  // Need to perform some detailed calculations to get the vertical spacing just right...

  // Get the height of some important elements
  var wm_ht = $('.canada-wm img').height();
  var ln_ht = $('#footer-sect .link:first').height();

  // Position the footer links. For wide screens, the wordmark is over to the right, so we only need to leave the same margin
  // at the bottom. But for mobile screens, we use 3x the wordmark height.
  if (ww > 680) {
    $('#footer-sect .link').css({'padding-bottom': wm_ht+'px'});
  } else {
    $('#footer-sect .link').css({'padding-bottom': (wm_ht*3)+'px'});
  }

  // Determine the current auto-height of the footer section
  var footer_ht = $('#footer-sect').css({height: 'auto'}).height();

  // We want to prevent scrolling, so check the available height. If not enough, then reduce the top-margin in #pm-sect.
  // Otherwise increase the top-margin in #pm-sect up to 400px, then if more space is available, increase the height of the footer.
  $('#pm-sect').css({'margin-top': 0});
  var main_ht = $('#main').height();
  var free_ht = wh - main_ht - footer_ht;
  
  if (free_ht > 0) {
    if (free_ht > 400) {
      $('#pm-sect').css({'margin-top': '400px'});
      footer_ht += free_ht - 400;
    } else {
      $('#pm-sect').css({'margin-top': free_ht+'px'});
    }
  }
  
  $('#footer-sect').css({height: footer_ht+'px'});

  // Handle the Canada-wordmark positioning. It should always be positioned at the bottom but with a margin below
  // equal to the height of the image. But we don't use css margins because it iterferes with other height calculations.
  $('.canada-wm img').css({top: (footer_ht-(wm_ht*2))+'px'});

  var bodyh = $('body').height();
  var bodyw = $('body').width();
  var body_aspect = bodyw / bodyh;
  var bpx = 0;
  var bpy = 0;

  if (body_aspect > img_aspect) {
    var scl = bodyw / img_w;
    var bsw = img_w * scl;
    var bsh = img_h * scl;
    var bcx = img_cx * scl;
    var bcy = img_cy * scl;
    var clip = (bsh - bodyh) / 2;
    bpy = bsh / 2 - bcy - clip;
    var minpy = bodyh-bsh;
    if (bpy < minpy) bpy = minpy;
    if (bpy > 0) bpy = 0;
  } else {
    var scl = bodyh / img_h;
    var bsh = img_h * scl;
    var bsw = img_w * scl;
    var bcx = img_cx * scl;
    var bcy = img_cy * scl;
    var clip = (bsw - bodyw) / 2;
    bpx = bsw / 2 - bcx - clip;
    var minpx = bodyw-bsw;
    if (bpx > 0) bpx = 0;
    else if (bpx < minpx) bpx = minpx;
  }

  $('body').css({'background-size': bsw+'px '+bsh+'px', 'background-position': bpx+'px '+bpy+'px'});
}

function sc_calc(n, nmin, nmax, fmin, fmax) {
  if (n > nmax) {
    return fmax;
  }
  else if (n < nmin) {
    return fmin;
  }
  var m = (fmax-fmin) / (nmax-nmin);
  var x = (n-nmin) * m + fmin;
  if (x < 0) x = 0;
  return x;
}
