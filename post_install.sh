#!/bin/bash

printf "execute post_install.sh\n";

trap "sudo configureSettingsFile" SIGINT SIGTERM

live=0
if [ -z $1 ]; then
  echo "dev environment setup.\n";
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
  if ! grep -q "wxt config_sync_directory" $settings_file; then
    printf "Setting your config sync folder to modules/custom/config\n";
    chmod 664 $settings_file;
    echo "//wxt config_sync_directory" >> $settings_file;
    echo "\$settings['config_sync_directory'] = 'modules/custom/config';" >> $settings_file;
    hashsalt=`drush php-eval 'echo \Drupal\Component\Utility\Crypt::randomBytesBase64(55)'`;
    echo "\$settings['hash_salt'] = '$hashsalt';" >> $settings_file;
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
}

configureSettingsFile

cp custom/splash/.htaccess html/.htaccess
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

  if [ -z $passvar ]; then
    echo "\n";
    read -t 60 -p 'Mysql database Username: default (60 seconds) is: username:' uservar
  fi
  if [ -z $uservar ]; then
    echo "\n";
    read -t 60 -sp 'Mysql database Password: default (60 seconds) is: password:' passvar
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

