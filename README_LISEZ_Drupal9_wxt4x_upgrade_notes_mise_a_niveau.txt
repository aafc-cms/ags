
 Prior to switching to the 9.x branch please do the following:

drush pmu lighting -y;
drush pmu lighting_install -y;
drush pmu wxt_ext_translation -y;

then checkout the 9.x branch

like so from your project root:

git checkout 9.x;
git pull;
composer install;
drush cr;
drush updb -y;


PREREQUISITES for Drupal 9:

Must have at least MySql 5.7
Must have at least MariaDB 10.3
Must have at least php 7.3.x



