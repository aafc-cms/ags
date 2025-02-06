#!/bin/bash

printf "execute post_install.sh\n";

trap "sudo configureSettingsFile" SIGINT SIGTERM

RED='\033[0;31m'
VERT='\033[0;32m'
BOLD='\033[1m' # BOLD
NC='\033[0m' # No Color

live=0
if [ -z $ENV_NAME ]; then
  # Do nothing.
  sed -i "s+^error_level: .*$+error_level: all+g" custom/config/splits/dev/system.logging.yml
else
  if [ $ENV_NAME == "prod" ]; then
    sed -i "s+^error_level: .*$+error_level: some+g" custom/config/splits/live/system.logging.yml
  else
    sed -i "s+^error_level: .*$+error_level: all+g" custom/config/splits/dev/system.logging.yml
    sed -i "s+^error_level: .*$+error_level: all+g" custom/config/splits/live/system.logging.yml
  fi
fi
if [ -z $1 ]; then
  echo "dev environment setup.";
  if [ -f custom/config/splits/dev/git_status.settings.yml ]; then
    sed -i "s+^repository_root: .*$+repository_root: `pwd`+g" custom/config/splits/dev/git_status.settings.yml
  fi
else
  if [ $1 == "live" ]; then
    echo "live environment setup.";
    live=1
  fi
fi
if [ "`hostname`" == "ryzen" ] && [ -f custom/config/splits/live/git_status.settings.yml ]; then
  live=1
  sed -i "s+^repository_root: .*$+repository_root: `pwd`+g" custom/config/splits/live/git_status.settings.yml
fi
configureSettingsFile () {
  if [ ! -f html/sites/default/settings.php ]; then
    printf "Creating your settings.php file\n";
    chmod 775 html/sites/default;
    cp html/sites/default/default.settings.php html/sites/default/settings.php
    chmod 664 html/sites/default/settings.php
    chown --reference=. html/sites/default/settings.php
    mkdir html/sites/default/files
    chown --reference=. html/sites/default/files
    chmod 775 html/sites/default/files
  fi

  settings_file=html/sites/default/settings.php;
  settings_local_file=html/sites/default/settings.local.php;

  if [ ! -f $settings_local_file ]; then
    touch $settings_local_file
    echo "<?php" >> $settings_local_file;
    echo "" >> $settings_local_file;
  fi
  if ! grep -q "wxt config_sync_directory" $settings_file; then
    printf "Setting your config sync folder to modules/custom/config\n";
    chmod 664 $settings_file;
    echo "//wxt config_sync_directory" >> $settings_file;
    echo "\$settings['config_sync_directory'] = 'modules/custom/config/sync';" >> $settings_file;
    hashsalt=`drush php-eval 'echo \Drupal\Component\Utility\Crypt::randomBytesBase64(55)'`;
    echo "\$settings['hash_salt'] = '$hashsalt';" >> $settings_file;
  fi
  if ! grep -q 'sites/default/files/private' $settings_local_file; then
    if ! grep -q '^if (file_exists($app_root . ''/'' . $site_path . ''/settings.local.php' $settings_file; then
      echo "";
      echo "if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {" >> $settings_file;
      echo "  include \$app_root . '/' . \$site_path . '/settings.local.php';" >> $settings_file;
      echo "}" >> $settings_file;
    fi
    if ! grep -q 'file_private_path' $settings_local_file; then
      echo "\$settings['file_private_path'] = 'sites/default/files/private';" >> $settings_local_file;
    fi
  fi

  if ! grep -q "config_split.config_split.dev" $settings_file; then
    printf "Setting up config_split for the first time.";
    chmod 775 html/sites/default;
    chmod 664 $settings_file;
    echo "\$config['config_split.config_split.dev']['status'] = TRUE; #config split DEV, do not remove this" >> $settings_file;
    echo "\$config['config_split.config_split.live']['status'] = FALSE; #config split LIVE, do not remove this" >> $settings_file;
  fi

  if [ $live -eq 1 ]; then
    chmod 775 html/sites/default;
    chmod 664 $settings_file;
    ./post_install_helper.php "force_split=live";
    echo "";
  else
    chmod 775 html/sites/default;
    chmod 664 $settings_file;
    ./post_install_helper.php "force_split=dev";
    echo "";
  fi

  # Fix previously configured environments.
  ./post_install_helper.php file_path="$settings_file" old_text="'modules/custom/config'" new_text="'modules/custom/config/sync'"

}

configureSettingsFile

cp custom/splash/.htaccess html/.htaccess
htaccess_file=html/.htaccess
if ! grep -q "upgrade-insecure-requests" $htaccess_file; then
  if [ -z $1 ]; then
    echo "dev environment setup.\n";
    echo "`hostname`" > temptesthostname.txt
    if grep -q "ryzen" temptesthostname.txt; then
      #echo "Ensure header always sets Content-Security-Policy. (check post_install.sh)";
      search_str="^( +)Header always set X-Content-Type-Options nosniff";
      new_setting="\1Header always set X-Content-Type-Options nosniff\n\1Header always set Content-Security-Policy \"upgrade-insecure-requests;\"\n"
      #sed -r "s/${search_str}/${new_setting}/gm" $htaccess_file > ${htaccess_file}_temp;
      #cp ${htaccess_file}_temp ${htaccess_file}
    else
      echo "This environment probably does not need the upgrade-insecure-requests";
    fi
    rm temptesthostname.txt
  else
    if [ $1 == "live" ]; then
      #echo "Ensure header always sets Content-Security-Policy for live environment. (check post_install.sh)";
      search_str="^( +)Header always set X-Content-Type-Options nosniff";
      new_setting="\1Header always set X-Content-Type-Options nosniff\n\1Header always set Content-Security-Policy \"upgrade-insecure-requests;\"\n"
      #sed -r "s/${search_str}/${new_setting}/gm" $htaccess_file > ${htaccess_file}_temp;
      #cp ${htaccess_file}_temp ${htaccess_file}
    fi
  fi
fi

if [ ! -d "html/libraries/chartjs" ]; then
  echo "wget https://github.com/chartjs/Chart.js/releases/download/v3.9.1/chart.js-3.9.1.tgz";
        wget https://github.com/chartjs/Chart.js/releases/download/v3.9.1/chart.js-3.9.1.tgz
  set -x;
  tar -pxzf chart.js-3.9.1.tgz
  rm chart.js-3.9.1.tgz
  mkdir html/libraries/chartjs;
  mv package/dist html/libraries/chartjs
  rm package -rf;
  set +x;
fi
if [ -d "html/libraries/jquery.inputmask/dist/min" ]; then
  echo "fix jquery inputmask distribution"
  echo "cp html/libraries/jquery.inputmask/dist/min/jquery.inputmask.bundle.min.js html/libraries/jquery.inputmask/dist/jquery.inputmask.min.js;"
        cp html/libraries/jquery.inputmask/dist/min/jquery.inputmask.bundle.min.js html/libraries/jquery.inputmask/dist/jquery.inputmask.min.js;
fi
if [ ! -d "html/libraries/jquery-ui-touch-punch" ]; then
  echo "mkdir html/libraries/jquery-ui-touch-punch;"
        mkdir html/libraries/jquery-ui-touch-punch;
  echo "wget https://raw.githubusercontent.com/furf/jquery-ui-touch-punch/master/jquery.ui.touch-punch.min.js;"
        wget https://raw.githubusercontent.com/furf/jquery-ui-touch-punch/master/jquery.ui.touch-punch.min.js;
  echo "mv jquery.ui.touch-punch.min.js html/libraries/jquery-ui-touch-punch;"
        mv jquery.ui.touch-punch.min.js html/libraries/jquery-ui-touch-punch;
fi

if [ -f html/splash.php ] && [ ! -L html/splash.php ]; then
  echo "rm html/splash.php"
        rm html/splash.php
fi
if [ ! -L html/splash.php ]; then
  echo "chmod 775 html"
        chmod 775 html
  echo "cd html"
        cd html
  echo "ln -s ../custom/splash/splash.php splash.php"
        ln -s ../custom/splash/splash.php splash.php
  echo "cd ..;"
        cd ..;
fi
if [ ! -L html/splash-fancy.php ]; then
  echo "cd html"
        cd html
  echo "ln -s ../custom/splash/splash-fancy.php splash-fancy.php"
        ln -s ../custom/splash/splash-fancy.php splash-fancy.php
  echo "cd .."
        cd ..
fi
if [ ! -L html/sites/default/splash ]; then
  echo "chmod 775 html/sites/default"
        chmod 775 html/sites/default
  echo "pushd html/sites/default;"
        pushd html/sites/default;
  echo "ln -s ../../../custom/splash/sites/default/splash splash"
        ln -s ../../../custom/splash/sites/default/splash splash
  echo "popd;"
        popd;
fi
if [ ! -L html/sites/default/splash-fancy ]; then
  pushd html/sites/default;
  ln -s ../../../custom/splash/sites/default/splash-fancy splash-fancy
  popd;
fi
if [ ! -L html/sites/default/files/splashimages ]; then
  pushd html/sites/default/files;
  ln -s ../../../../custom/splash/sites/default/files/splashimages splashimages
  popd;
fi

if [ $live -eq 1 ]; then
  echo "Do not use minified css";
else
  #Use minified theme.min.css.
  #cp html/libraries/theme-gc-intranet/css/theme.css html/libraries/theme-gc-intranet/css/theme.min.css
  # Uncomment the above line if needing the source css for the gc intranet theme library css.
  echo "Use the minified css in dev (for now)."
fi

dbSetupTest=0

if grep -q "namespace' => 'Drupal" html/sites/default/settings.php
then
  echo "Database settings in html/sites/default/settings.php was previously configured.";
  if grep -q " 'namespace' => 'Drupal" html/sites/default/settings.php
  then
    echo "Database settings upgrade for Drupal 9.4.5.";
    chmod 775 html/sites/default;
    chmod 775 html/sites/default/settings.php;
    #/*'namespace' => 'Drupal\Core\Database\Driver\mysql',*/
    sed -i "s+ 'namespace' => 'Drupal+\#'namespace' => 'Drupal+g" html/sites/default/settings.php
  fi
  dbSetupTest=1;
else
  echo "chmod 775 html/sites/default"
        chmod 775 html/sites/default
  echo "Assuming that the mysql database name is the same as the username.\n";
  printf "\n";
  read -t 60 -p 'Mysql database Username: default (60 seconds) is: username:' uservar
  read -t 60 -sp 'Mysql database Password: default (60 seconds) is: password:' passvar
  settings_file=html/sites/default/settings.php;
  printf "\n";
  read -t 2 -p "Confirm username $uservar" confirm
  printf "\n";

  if [ -z $passvar ]; then
    passvar=`whoami`;
  fi
  if [ -z $uservar ]; then
    userver=`whoami`;
  fi
  echo "chmod 664 $settings_file"
        chmod 664 $settings_file
  echo "\$databases['default']['default'] = array (" >> $settings_file 
  echo "  'database' => '$uservar'," >> $settings_file
  echo "    'username' => '$uservar'," >> $settings_file
  echo "    'password' => '$passvar'," >> $settings_file
  echo "    'prefix' => ''," >> $settings_file
  echo "    'host' => 'localhost'," >> $settings_file
  echo "    'port' => '3306'," >> $settings_file
  echo "   #'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql'," >> $settings_file
  echo "   #'autoload' => 'modules/mysql/src/Driver/Database/mysql/'," >> $settings_file
  echo "    'driver' => 'mysql'," >> $settings_file
  echo "  );" >> $settings_file
  echo "chmod 555 html/sites/default"
        chmod 555 html/sites/default
fi

echo ""
echo "**** behatags setup/verifications ****"
echo ""
echo "pushd tests/behatags"
      pushd tests/behatags
echo "composer install;"
      composer install;
echo "pushd vendor;"
      pushd vendor;
#set -x;
#echo "if ! patch -R -p1 -s -f --dry-run < ../gherkin_language_french.patch; then"
      if ! patch -R -p1 -s -f --dry-run < ../gherkin_language_french.patch; then
echo "  patch -p1 < ../gherkin_language_french.patch";
        patch -p1 < ../gherkin_language_french.patch
      fi
#echo "if ! patch -R -p1 -s -f --dry-run < ../Selenium2Driver_firefox_acceptInsecureCerts.patch; then"
      if ! patch -R -p1 -s -f --dry-run < ../Selenium2Driver_firefox_acceptInsecureCerts.patch; then
echo "  patch -p1 < ../Selenium2Driver_firefox_acceptInsecureCerts.patch";
        patch -p1 < ../Selenium2Driver_firefox_acceptInsecureCerts.patch
      fi
echo "popd;"
      popd;
#set +x;
current_branch=`git branch 2> /dev/null | sed -e '/^[^*]/d' -e 's/* \(.*\)/\1/'`
current_upstream_url=`git remote get-url $(git for-each-ref --format='%(upstream:short)' $(git symbolic-ref -q HEAD)|cut -d/ -f1)`;
echo $current_upstream_url
if [[ "$current_upstream_url" == "https://gitlab.w3usine.com/qa/behatags.git" ]]; then
  if git ls-remote --exit-code origin &>/dev/null; then
    echo "git remote set-url origin https://gitlab.com/agrcms/behatags.git"
          git remote set-url origin https://gitlab.com/agrcms/behatags.git
  else
    echo "behat package is using origin remote https://gitlab.com/agrcms/behatags.git";
  fi

  if git ls-remote --exit-code composer &>/dev/null; then
    echo "git remote set-url composer https://gitlab.com/agrcms/behatags.git"
          git remote set-url composer https://gitlab.com/agrcms/behatags.git
  else
    echo "behat package is using composer remote https://gitlab.com/agrcms/behatags.git";
  fi

else
  echo "The correct behatags repository remote is currently in place https://gitlab.com/agrcms/behatags.git";
fi

if [ ! -f ".gitignore" ]; then
  echo "vendor" > .gitignore
  if [[ "$current_branch" == "master" ]]; then
    if [ -z "$(git status --untracked-files=no --porcelain)" ]; then 
      # Repo is clean.
      echo "git pull;"
            git pull;
    else
      # Repo is not clean
      echo "git stash;"
            git stash;
      echo "git pull;"
            git pull;
      echo "git stash pop;"
            git stash pop;
    fi
  else
    echo "YOUR CURRENT BEHAT BRANCH IS NOT \"master\", IT IS: \"$current_branch\", therefore skipping git pull";
  fi
fi
echo "popd;"
      popd;
echo ""

if [ ! -d "keys" ]; then
  echo -e "${RED}mkdir${BOLD} keys;${NC}${VERT} #For key override file storage.${NC}"
  mkdir keys
fi

if [ ! -f "keys/smtp_password" ]; then
  echo -e "${RED}mkdir${BOLD} keys/smtp_password created;${NC}${VERT} #Edit this file and insert the expected value.${NC}"
  touch keys/smtp_password;
fi
if [ ! -f "keys/smtp_user" ]; then
  echo -e "${RED}mkdir${BOLD} keys/smtp_user created;${NC}${VERT} #Edit this file and insert the expected value.${NC}"
  touch keys/smtp_user;
else
  echo -e "${RED}${BOLD}keys${NC}${VERT} folder exists.${NC}"
fi
if [ ! -d "/tmp/debug-chrome" ]; then
  mkdir /tmp/debug-chrome
fi
if [ ! -d "/tmp/debug-phantomjs" ]; then
  mkdir /tmp/debug-phantomjs
fi
if [ ! -d "/tmp/behat-downloads" ]; then
  mkdir /tmp/behat-downloads
fi
#if ! command -v phantomjs &> /dev/null
#then
#    echo "phantomjs is not currently installed, to install it run this script:"
#    echo "sudo bash post_install_other.sh";
#fi

echo "**** End behatags setup/verifications ****"

drush status --field=Database 2>/dev/null > test-connection.txt || true; # Ignore errors.

if grep -q "Connected" test-connection.txt; then
  echo "";
  echo "Connected to the database, setting up the db views now.";
  #Run Create or Replace View sql command
  echo  "Creating view: drush sql-query --file=../custom/dbviews/search_node_url.sql 2>/dev/null";
                        drush sql-query --file=../custom/dbviews/search_node_url.sql 2>/dev/null;
else
  echo "";
  echo "The database is not yet configured, cannot install the db views at this time.";
fi
rm test-connection.txt;

echo -e "cp build/wet-boew.js html/libraries/wet-boew/js/wet-boew.js  ${VERT}${BOLD}REPLACE v4.0.83 with v4.0.85-beta1${NC}"
      cp build/wet-boew.js html/libraries/wet-boew/js/wet-boew.js
echo -e "cp build/wet-boew.js html/libraries/wet-boew/js/wet-boew.min.js  ${VERT}${BOLD}REPLACE v4.0.83 with v4.0.85-beta1${NC}"
      cp build/wet-boew.js html/libraries/wet-boew/js/wet-boew.min.js
if [ ! -f "html/libraries/wet-boew/js/purify.js.map" ]; then
  echo -e "${BOLD}touch${NC} html/libraries/wet-boew/js/${BOLD}purify.js.map${NC};"
           touch html/libraries/wet-boew/js/purify.js.map
fi
