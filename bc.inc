<?php

require_once('common.inc');

function region_create_bc()
{
	return create_region("British Columbia",
		array(
			create_loc('Whistler',     'bc_whistler_report.php',
			           'WH', 50, 122,
			           'http://www.whistlerblackcomb.com/weather/snowreport/index.htm'),
		));
}

function resorts_bc_get()
{
	return region_create_bc()->locations;
}
