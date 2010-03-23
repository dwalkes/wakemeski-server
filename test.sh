#!/bin/bash

SERVER=http://bettykrocks.com/skireport/test

function usage
{
    echo "Usage: $0 [-s <server>] [-c]"
    echo " -s defaults to ${SERVER}"
    echo " -c means don't check for cache"
    exit 1
}

while getopts   "s:c" optn; do
    case    $optn   in
        s   )   SERVER=$OPTARG;;
        c   )   CACHE="&nocache=1" ;;
        \?  )   echo "invalid option: $OPTARG" ; usage;  exit  -1;;
    esac
done

LOCATIONS="
	nwac_report.php?location=OSOALP
	utah_report.php?location=ATA
	nm_report.php?location=SA
	co_report.php?location=PG
	co_report2.php?location=VA
	mt_report.php?location=BS
	eu_report.php?location=fchm
"

for loc in ${LOCATIONS}; do
	echo "- getting ${SERVER}/${loc}"
	curl ${SERVER}/${loc}${CACHE}
	echo "-----------------------------------------------------------------"
done
