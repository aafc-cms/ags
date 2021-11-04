#!/bin/bash
if ! command -v phantomjs &> /dev/null
then
    echo "phantomjs could not be found, lets install it"
    sudo apt-get update;
    sudo apt-get install build-essential chrpath libssl-dev libxft-dev 
    sudo apt-get install libfreetype6 libfreetype6-dev libfontconfig1 libfontconfig1-dev
    pushd /tmp/
    wget https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2
    sudo tar xjf phantomjs-2.1.1-linux-x86_64.tar.bz2 -C /usr/local/share/
    sudo ln -sf /usr/local/share/phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/local/bin
version=`phantomjs --version`
    echo "phantomjs is installed as follows: $version";
fi

