#!/bin/bash

printf "execute post_install.sh\n";

if [ ! -f html/sites/default/settings.php ]; then
  printf "Creating your settings.php file\n";
  sudo chmod 775 html/sites/default;
  cp html/sites/default/default.settings.php html/sites/default/settings.php
  sudo chmod 664 html/sites/default/settings.php
  sudo chown --reference=. html/sites/default/settings.php
  sudo mkdir html/sites/default/files
  sudo chown --reference=. html/sites/default/files
  sudo chmod 775 html/sites/default/files
fi

if ! grep -q "wxt config_sync_directory" html/sites/default/settings.php; then
  printf "Setting your config sync folder to modules/custom/config\n";
  sudo chmod 664 html/sites/default/settings.php
  echo "//wxt config_sync_directory" >> html/sites/default/settings.php
  echo "\$settings['config_sync_directory'] = 'modules/custom/config';" >> html/sites/default/settings.php
fi

cp custom/splash/.htaccess html/.htaccess
if [ ! -L html/splash.php ]; then
  cd html
  ln -s ../custom/splash/splash.php splash.php
  cd ..;
fi
if [ ! -L html/sites/default/splash.js ]; then
  pushd html/sites/default;
  ln -s ../../../custom/splash/sites/default/splash.js splash.js
  popd;
fi
if [ ! -L html/sites/default/splash.css ]; then
  pushd html/sites/default;
  ln -s ../../../custom/splash/sites/default/splash.css splash.css
  popd;
fi
if [ ! -L html/sites/default/files/splashimages ]; then
  pushd html/sites/default/files;
  ln -s ../../../../custom/splash/sites/default/files/splashimages splashimages
  popd;
fi
cp custom/splash/sites/default/*.png html/sites/default/.

