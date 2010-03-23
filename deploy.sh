#!/bin/sh

GITFILE=/tmp/wakemeski.git.txt

DIR=$(dirname $(readlink -f $0))

cd $DIR

git log -1 > $GITFILE

scp *.php *.inc $GITFILE bettykro@bettykrocks.com:/home/bettykro/www/skireport/
rm $GITFILE
