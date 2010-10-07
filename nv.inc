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
