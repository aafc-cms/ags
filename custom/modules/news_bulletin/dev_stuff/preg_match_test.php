<?php
/* https://regex101.com/codegen?language=php */
$re = '/<drupal-media.*\B<\/drupal-media>/m';
$str = '
<ul>
	<li><a href="https://collab.agr.gc.ca/co/pdn-rph/SitePages/Home.aspx" target="_blank">Persons with Disabilities Network</a></li>
	<li><a href="https://www.canada.ca/en/employment-social-development/programs/accessible-people-disabilities/act-summary.html" target="_blank">Proposed Accessible Canada Act</a></li>
	<li><a href="https://collab.agr.gc.ca/co/eedi-emedi/SitePages/Home.aspx" target="_blank">Diversity and Inclusion at AAFC</a></li>
</ul>

<p><img class="center-block" src="/sites/default/files/legacy/resources/prod/news/images/APDImage.png" /></p>

<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="7be1c092-09bb-46bd-844c-80b92f34e1c9"></drupal-media> blah blah
';

preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);

// Print the entire match result
var_dump($matches);
echo "TEST\n\n";
echo $matches[0][0];
echo "\n\n";
$regex2 = '/<img.*\B \/>/m';

$matches = null;
preg_match_all($regex2, $str, $matches, PREG_SET_ORDER, 0);
echo "TEST2\n\n";
echo $matches[0][0];
echo "\n\n";
