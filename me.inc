<?php

require_once('common.inc');

function region_create_me()
{
	return create_region("Maine",
		array(
			create_loc('Sugarloaf',     'me_sugarloaf_report.php',
			           'SL', 45.031808, -70.313247),
			create_loc('Sunday River',   'me_sunday_river.php',
			           'SR', 44.468767, -70.848186,
			           'http://www.sundayriver.com/mobile/index.asp'),
		));
}

function resorts_me_get()
{
	return region_create_me()->locations;
}
