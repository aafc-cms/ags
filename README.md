Composer Project template for Drupal
====================================

[![Build Status][ci-badge]][ci]

Drupal WxT codebase for `<site-name>`.

## Requirements

* [Composer][composer]
* [Node][node]

## New Project (stable tag)

```sh
composer install
composer update drupalwxt/wxt
# Based on drupalwxt
# No need to run this: composer create-project drupalwxt/wxt-project:3.0.6 site-name
```

## New Project (dev)

```sh
composer install
# Based on drupalwxt
# No need to run this: composer create-project drupalwxt/wxt-project:8.x-dev site-name
```

## POST INSTALLATION
# add this line to your settings.php after installation.
$config_directories['sync'] = 'config/sync';
# now the config will be grabbed from the config folder in the root of your drupal installation.

## Maintenance

List of common commands are as follows:

| Task                                            | Composer                                               |
|-------------------------------------------------|--------------------------------------------------------|
| Latest version of a contributed project         | ```composer require drupal/PROJECT_NAME:8.*```         |
| Specific version of a contributed project       | ```composer require drupal/PROJECT_NAME:8.1.0-beta5``` |
| Updating all projects including Drupal Core     | ```composer update```                                  |
| Updating a single contributed project           | ```composer update drupal/PROJECT_NAME```              |
| Updating Drupal Core exclusively                | ```composer update drupal/core```                      |


[ci]:                       https://travis-ci.org/drupalwxt/site-wxt
[ci-badge]:                 https://travis-ci.org/drupalwxt/site-wxt.svg?branch=8.x
[composer]:                 https://getcomposer.org
[node]:                     https://nodejs.org
[docker-scaffold-readme]:   https://github.com/drupal-composer-ext/drupal-scaffold-docker/blob/8.x/README.md
[docker-readme]:            https://github.com/drupal-composer-ext/drupal-scaffold-docker/blob/8.x/template/docker/README.md

Testing by Simon

## ddev

- https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#linux
- The install for Debian/Ubuntu is a bit confusing, bunch of commands to get working.
- Once installed, only three commands to get running. https://ddev.readthedocs.io/en/stable/users/project/

## Passwords

drush cset symfony_mailer_lite.symfony_mailer_lite_transport.smtp configuration.user 'NEW_USERNAME' -y
drush cset symfony_mailer_lite.symfony_mailer_lite_transport.smtp configuration.pass 'NEW_PASSWORD' -y

