#!/bin/bash

SERVER=http://bettykrocks.com/skireport/test

function usage
{
    echo "Usage: $0 [-s <server>] [-c]"
    echo " -s defaults to ${SERVER}"
    echo " -c means don't check for cache"
    exit 1
}

while getopts   "s:cv" optn; do
    case    $optn   in
        s   )   SERVER=$OPTARG;;
        c   )   CACHE="&nocache=1" ;;
	v   )   VERBOSE=1;;
        \?  )   echo "invalid option: $OPTARG" ; usage;  exit  -1;;
    esac
done

LOCATIONS="
	nwac_report.php?location=OSOALP
	utah_report.php?location=ATA
	id_sunvalley_report.php?location=SV
	id_brundage_report.php?location=BD
	nm_report.php?location=SA
	co_report.php?location=PG
	co_report2.php?location=VA
	mt_report.php?location=WF
	eu_report.php?location=fchm
	eu_report.php?location=spce
	vt_report.php?location=KR
"

#required properties to ensure get reported
PROPERTIES="
	snow.total
	snow.daily
	snow.fresh
	snow.units
	date
	weather.url
	weather.icon
	location
	location.info
	cache.found
"
FAILED=0
for loc in ${LOCATIONS}; do
	output=/tmp/wakemeski.$$
	echo "= testing: ${SERVER}/${loc}"
	curl -o $output -s ${SERVER}/${loc}${CACHE}
	if [ $? -eq 0 ] ; then
		[ $VERBOSE ] && cat $output
		#ensure each required report property is found:
		for p in ${PROPERTIES}; do
			grep "$p[[:space:]]*=" $output > /dev/null
			if [ $? -ne 0 ] ; then
				echo "ERROR: missing required property($p)"
				FAILED=1
			fi
		done
	else
		echo "ERROR getting report"
		FAILED=1
	fi
	rm -f $output
done

exit $FAILED
