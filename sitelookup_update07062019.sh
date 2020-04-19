#!/bin/bash

wget http://layer3.mochahost.com/tgeroff/sitelookup/sitelookup -O /usr/local/bin/sitelookup ; chmod 750 /usr/local/bin/sitelookup
mkdir /opt/mochapm/MochaPW -p
wget http://layer3.mochahost.com/tgeroff/sitelookup/PwCache.pm -O /opt/mochapm/MochaPW/PwCache.pm
wget http://layer3.mochahost.com/tgeroff/sitelookup/ApacheConf.pm -O /opt/mochapm/MochaPW/ApacheConf.pm

