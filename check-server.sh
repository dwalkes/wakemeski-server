#!/bin/sh

# runs the wakemeski test script and emails a list of people
# if an error is encountered

RECIPIENTS="doanac@gmail.com"

dir=$(dirname $(readlink -f $0))

# allow a "-v" option which means email the report contents out
while getopts   "v" optn; do
    case    $optn   in
        v   )   VERBOSE="-v";;
        \?  )   echo "invalid option: $OPTARG" ; exit  -1;;
    esac
done

output="/tmp/wakemeski-cron.$$"
trap "rm -f $output" EXIT

$dir/test.sh $VERBOSE -s http://bettykrocks.com/skireport -c >>$output 2>&1
rc=$?
if [ $rc -ne 0 ] || [ $VERBOSE ] ; then
	SUBJ="WakeMeSki Server Report"
	[ $rc -ne 0 ] && SUBJ="WakeMeSki Server Error!!!"
	{
		echo "Subject: $SUBJ"
		echo 
		cat $output
	} | /usr/sbin/ssmtp $RECIPIENTS
fi
