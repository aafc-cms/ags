#!/bin/bash

# bash prompt which asks for email address
# to configure for current git repository
unset GIT_CONFIG

oldemail=$(git config user.email)
read -p "Your current email is ${oldemail:-not set}, enter a new email:  " newemail
newemail=${newemail:=$oldemail}
if [ "$newemail" != "" ] ; then git config --global user.email "$newemail"; echo "New email is $newemail"; fi


oldname=$(git config user.name)
read -p "Your current name is ${oldname:-not set}, enter a new name:  " newname
newemail=${newname:=$oldname}
if [ "$newname" != "" ] ; then git config --global user.name "$newname"; echo "New name is $newname"; fi

git config credential.helper store
echo "OK"


