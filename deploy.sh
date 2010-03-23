#!/bin/bash

USER=bettykro
SERVER=bettykrocks.com
LOC=/home/bettykro/www/skireport/

function usage
{
    echo "Usage: $0 [-u <user>] [-s <server>] [-d <dir>]"
    echo " -u defaults to ${USER}"
    echo " -s defaults to ${SERVER}"
    echo " -d defaults to ${LOC}"
    exit 1
}

while getopts   "u:s:d:" optn; do
    case    $optn   in
        u   )   USER=$OPTARG;;
        s   )   SERVER=$OPTARG;;
        d   )   LOC=$OPTARG;;
        \?  )   echo "invalid option: $OPTARG" ; usage;  exit  -1;;
    esac
done

GITFILE=/tmp/wakemeski.git.txt

DIR=$(dirname $(readlink -f $0))

cd $DIR

git log -1 > $GITFILE

scp *.php *.inc $GITFILE ${USER}@${SERVER}:${LOC}/
rm $GITFILE
