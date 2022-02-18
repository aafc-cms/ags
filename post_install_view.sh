#!/bin/env bash

#Run Create or Replace View sql command
echo  "Creating view: drush sql-query --file=../custom/dbviews/search_node_url.sql";
drush sql-query --file=../custom/dbviews/search_node_url.sql;