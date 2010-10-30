#!/bin/bash

# Get defaults
# Create a symbolic link to the appropriate defaults
# file
if [ ! -f defaults.sh ]; then
    echo "error no defaults.sh found. please set up symlink";
    exit -1
fi
. ./defaults.sh

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
