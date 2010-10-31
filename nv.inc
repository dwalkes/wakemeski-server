<?php

require_once('common.inc');

function region_create_nv()
{
	$co2_base = 'co_report2.php';

	return create_region("Nevada",
		array(
			create_loc('Heavenly',          $co2_base,
			           'HV', 38.934787,-119.940384,
			           'http://www.skiheavenly.com'),
		));
}

/*
 * Currently all nevada resorts use the co_report2 script.  If this
 * changes, will need to customize function below to only return
 * heavenly or the subset of resorts served by co_report2
 */
function resorts_nv_co_report2_get()
{
	return region_create_nv()->locations;
}
