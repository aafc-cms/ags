Idol Feed Rest API
==================

Idol Feed Rest API, which is a custom Drupal module, leverages Web Service Basic
 authentication module


## Installation
The module and all dependencies are included and configured in composer.json
file. It will be installed as a part of composer installation.

### Distribution
This module provides web service interface for IDOL Search engine integration.

The module is secured with Basic Authentication. Any authenticated user is able
to access it.
    requirements:
      _user_is_logged_in: 'TRUE'

The service can be accessed with following path "idol_feed_api/search"

The service accepts one parameter "sinceDate"

If there is no parameter or parameter value is empty, the API will return back all node results

If we specify a timestamp like "sinceDate=1583528057", The API will return back modified node results after the timestamp.

Example using curl command-line tool:
    curl --user user:password http://localhost/en/idol_feed_api/search   
    curl --user user:password http://localhost/en/idol_feed_api/search?sinceDate=1583528057
