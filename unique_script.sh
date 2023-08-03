#!/bin/bash

drush filehash:generate;
echo " ******* FILE HASH GENERATION IS COMPLETE *******"

echo " BEGIN DUPLICATE FILE REPORT ";
echo " ";
drush scr custom/unique_script.php

