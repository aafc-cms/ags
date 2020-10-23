#!/bin/bash

printf "execute post_install.sh\n";

trap "sudo configureSettingsFile" SIGINT SIGTERM

live=0
if [ -z $1 ]; then
  echo "dev environment setup.";
  if [ -f html/sites/default/default.settings.php ]; then
    sed -i "s+^repository_root: .*$+repository_root: `pwd`+g" custom/config/splits/dev/git_status.settings.yml
  fi
else
  if [ $1 == "live" ]; then
    echo "live environment setup.";
    live=1
  fi
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

  if ! grep -q "STRICT_TRANS_TABLES" $settings_file; then
    echo "`hostname`" > temptesthostname.txt
    if ! grep -q "ryzen" temptesthostname.txt; then
      search_str="^( +)'driver' => 'mysql',"
      new_db_init="\1'driver' => 'mysql',\n    'init_commands' => [\n      'sql_mode' => \"SET sql_mode = 'STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,TRADITIONAL,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'\",\n    ],"
      sed -r "s/${search_str}/${new_db_init}/gm" $settings_file > ${settings_file}_temp;
      cp ${settings_file}_temp ${settings_file}
    else
      echo "This environment does not need the init_commands";
    fi
    rm temptesthostname.txt
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
  else
    chmod 775 html/sites/default;
    chmod 664 $settings_file;
    ./post_install_helper.php "force_split=dev";
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
      echo "Ensure header always sets Content-Security-Policy. (check post_install.sh)";
      search_str="^( +)Header always set X-Content-Type-Options nosniff";
      new_setting="\1Header always set X-Content-Type-Options nosniff\n\1Header always set Content-Security-Policy \"upgrade-insecure-requests;\"\n"
      sed -r "s/${search_str}/${new_setting}/gm" $htaccess_file > ${htaccess_file}_temp;
      cp ${htaccess_file}_temp ${htaccess_file}
    else
      echo "This environment probably does not need the upgrade-insecure-requests";
    fi
    rm temptesthostname.txt
  else
    if [ $1 == "live" ]; then
      echo "Ensure header always sets Content-Security-Policy for live environment. (check post_install.sh)";
      search_str="^( +)Header always set X-Content-Type-Options nosniff";
      new_setting="\1Header always set X-Content-Type-Options nosniff\n\1Header always set Content-Security-Policy \"upgrade-insecure-requests;\"\n"
      sed -r "s/${search_str}/${new_setting}/gm" $htaccess_file > ${htaccess_file}_temp;
      cp ${htaccess_file}_temp ${htaccess_file}
    fi
  fi
fi

# Commenting out following lines, I tested and confirmed that have upgraded to wet-boew that no longer has this bug, no need to do this now.
# @TODO remove the next 4 commented out lines next release.
#rm html/libraries/wet-boew/js/wet-boew.js
#rm html/libraries/wet-boew/js/wet-boew.min.js
#ln -s ../../../../custom/modules/wxt_overrides/js/wb/wet-boew.js html/libraries/wet-boew/js/wet-boew.js
#ln -s ../../../../custom/modules/wxt_overrides/js/wb/wet-boew.js html/libraries/wet-boew/js/wet-boew.min.js

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

if [ ! -L html/splash.php ]; then
  cd html
  ln -s ../custom/splash/splash.php splash.php
  cd ..;
fi
if [ ! -L html/splash-fancy.php ]; then
  cd html
  ln -s ../custom/splash/splash-fancy.php splash-fancy.php
  cd ..;
fi
if [ ! -L html/sites/default/splash ]; then
  pushd html/sites/default;
  ln -s ../../../custom/splash/sites/default/splash splash
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
  echo "Database settings in html/sites/default/settings.php is already configured.";
  dbSetupTest=1;
else
  echo "Assuming that the mysql database name is the same as the username.\n";
  echo "\n";
  read -t 60 -p 'Mysql database Username: default (60 seconds) is: username:' uservar
  read -t 60 -sp 'Mysql database Password: default (60 seconds) is: password:' passvar
  settings_file=html/sites/default/settings.php;
  echo "\n";
  read -t 2 -p "Confirm username $uservar" confirm
  echo "\n";

  if [ -z $passvar ]; then
    passvar=`whoami`;
  fi
  if [ -z $uservar ]; then
    userver=`whoami`;
  fi
  echo "\$databases['default']['default'] = array (" >> $settings_file 
  echo "  'database' => '$uservar'," >> $settings_file
  echo "    'username' => '$uservar'," >> $settings_file
  echo "    'password' => '$passvar'," >> $settings_file
  echo "    'prefix' => ''," >> $settings_file
  echo "    'host' => 'localhost'," >> $settings_file
  echo "    'port' => '3306'," >> $settings_file
  echo "    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql'," >> $settings_file
  echo "    'driver' => 'mysql'," >> $settings_file
  echo "  );" >> $settings_file
fi

