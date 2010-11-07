<?php

require_once('common.inc');

function region_create_me()
{
	return create_region("Maine",
		array(
			create_loc('Sugarloaf',     'me_sugarloaf_report.php',
			           'SL', 45.031808, -70.313247),
		));
}

function resorts_me_get()
{
	return region_create_me()->locations;
}
