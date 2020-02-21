<?php
header('Cache-Control: no-cache');
?>
<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta property="og:title" content="Agriculture and Agri-Food Canada - Agriculture et Agroalimentaire Canada" />
<meta property="og:site_name" content="Agriculture and Agri-Food Canada - Agriculture et Agroalimentaire Canada">
<meta property="og:type" content="website">
<meta property="og:url" content="https://intranet.agr.gc.ca/agrisource">
<meta property="og:title" content="Agriculture and Agri-Food Canada - Agriculture et Agroalimentaire Canada">
<meta property="og:description" content="Agrisource">
<meta property="og:image" content="https://pm.gc.ca/sites/pm/files/inline-images/wordmark_0.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="https://intranet.agr.gc.ca/agrisource">
<meta name="twitter:title" content="Agriculture and Agri-Food Canada - Agriculture et Agroalimentaire Canada">
<meta name="twitter:description" content="Agrisource">
<meta name="twitter:image" content="https://pm.gc.ca/sites/pm/files/inline-images/wordmark_0.png">
<meta name="twitter:image:src" content="https://pm.gc.ca/sites/pm/files/inline-images/wordmark_0.png">
<title>Agrisource</title>
<link href="https://fonts.googleapis.com/css?family=Hind" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="/sites/default/splash-fancy/splash.css">
<script src="/core/assets/vendor/jquery/jquery.min.js?v=3.2.1"></script>
<script src="/sites/default/splash-fancy/splash.js"></script>
<script>var _gaq = _gaq || [];_gaq.push(["_setAccount", "UA-10314923-1"]);_gaq.push(["_gat._anonymizeIp"]);_gaq.push(["_trackPageview"]);(function() {var ga = document.createElement("script");ga.type = "text/javascript";ga.async = true;ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";var s = document.getElementsByTagName("script")[0];s.parentNode.insertBefore(ga, s);})();</script>
<?php
$bk_image = '';
if ($handle = opendir('sites/default/files/splashimages')) {
  $img_files = array();
  while (false !== ($file = readdir($handle))) {
    if ($file[0] == '.' or is_dir($file)) continue;
    if (!preg_match('/\.jpe?g$/', $file) && !preg_match('/\.png$/', $file)) continue;
    $img_files[] = $file;
  }
  closedir($handle);
  $max = count($img_files) - 1;
  $file_num = null;
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['bk']) && !empty($_POST['bk'])) {
      $file_num = $_POST['bk'] + 1;
      if ($file_num > $max) $file_num = 0;
    }
  }  
  if ($file_num === null) {
    $file_num = mt_rand(0, $max);
  }
  $bk_file = $img_files[$file_num];
  $bk_image = "sites/default/files/splashimages/$bk_file";
  $img_size = getimagesize($bk_image);
  $img_w = $img_size[0];
  $img_h = $img_size[1];
  if (preg_match('/\((\d+),(\d+)\)\./', $bk_image, $matches)) {
    $img_cx = $matches[1];
    $img_cy = $matches[2];
  } else if (preg_match('/\.(\d+)\.(\d+)\.(gif|jpg)$/', $bk_image, $matches)) {
    $img_cx = $matches[1];
    $img_cy = $matches[2];
  }
  if (!isset($img_cx)) {
    $img_cx = (int) ($img_w / 2);
    $img_cy = (int) ($img_h / 2);
  }
}
?>
<script type="text/javascript">
var img_w = <?php echo isset($img_size[0]) ? $img_size[0] : 1 ?>;
var img_h = <?php echo isset($img_size[1]) ? $img_size[1] : 1 ?>;
var img_aspect = img_w / img_h;
var img_cx = <?php echo isset($img_cx) ? $img_cx : 0 ?>;
var img_cy = <?php echo isset($img_cy) ? $img_cy : 0 ?>;
</script>
</head>

<body style="background-image: url('<?php echo $bk_image ?>'); background-repeat: no-repeat;">

<div id="outer-main">
  <div id="main">
    <div id="head-sect" class="css-mapw" data-attr="height" data-map="280,1200,64,100">
      <img src="/sites/default/splash-fancy/Canada-wordmark-white.png" alt="Agrisource" />
    </div>

    <div id="agrisource-sect">
      <div class="row">
        <div class="agrisource-block">
          <div class="agrisource-top css-mapw" data-attr="font-size" data-map="280,1200,14,40">
            <div class="agrisource-hd">
              <div class="en">Agrisource (English)</div>
              <div class="fr"><span lang="fr">Agrisource (français)</span></div>
            </div>
          </div>
          <h1 class="css-mapw" data-attr="font-size" data-map="280,1200,32,100">Welcome - Bienvenue</h1>
        </div>
      </div>
    </div>

    <div id="lang-sect" class="css-maph" data-attr="margin-top" data-map="300,1000,0,50" style="display: table; width: 100%">
      <div class="row" style="display: table-row">
        <div class="button en" style="display: table-cell; width: 50%; text-align: right; padding: 0 15px;"><a href="/en"><img class="css-mapw" src="/sites/default/splash-fancy/splash-button-en.png" data-attr="height" data-map="280,1200,20,40" alt="English website" /></a></div>
        <div class="button fr" style="display: table-cell; width: 50%; text-align: left; padding: 0 15px;"><a href="/fr"><img class="css-mapw" src="/sites/default/splash-fancy/splash-button-fr.png" data-attr="height" data-map="280,1200,20,40" alt="Site web en français" /></a></div>
      </div>
    </div>
  </div>

  <div id="footer-sect">
    <div class="canada-wm">
      <img src="https://pm.gc.ca/sites/pm/files/inline-images/wordmark_0.png" alt="Symbol of the Government of Canada / Symbole du gouvernement du Canada" />
    </div>
    <div class="row css-mapw" data-attr="font-size" data-map="280,1200,11,18">
      <div class="link en"><a href="/en/important-notices">Important Notices</a></div>
      <div class="link fr"><span lang="fr"><a href="/fr/avis-importants">Avis importants</a></span></div>
    </div>
  </div>
</div>

<form method="post" action="/"><input type="hidden" name="bk" value="<?php echo $file_num ?>"></form>
</body>
</html>
