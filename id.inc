<?php

require_once('common.inc');

function region_create_id()
{
	return create_region("Idaho",
		array(
			create_loc('Sun Valley',     'id_sunvalley_report.php',
			           'SV', 43.682739, -114.403404,
			           'http://www.sunvalley.com/mountain/weather/'),
		));
}

function resorts_id_get()
{
	return region_create_id()->locations;
}
