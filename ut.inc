<?php

require_once('common.inc');

function report_url($name)
{
	return 'http://www.skiutah.com/winter/members/'.$name.'/resort';
}

function region_create_ut()
{
	$ut_base = 'utah_report.php';

	return create_region("Utah",
		array(
			create_loc('Alta',              $ut_base,
			           'ATA', 40.57972, -111.6375,
			           report_url('alta_ski_area')),
			create_loc('Beaver Mountain',   $ut_base,
			           'BVR', 41.96833, -111.54083,
			           report_url('beaver_mountain')),
			create_loc('Brian Head',        $ut_base,
			           'BHR', 37.69194, -112.83722,
			           report_url('brian_head_resort')),
			create_loc('Brighton',          $ut_base,
			           'BRT', 40.6,     -111.58278,
			           report_url('brighton_ski_resort')),
			create_loc('The Canyons',       $ut_base,
			           'CNY', 40.68525, -111.556375,
			           report_url('the_canyons')),
			create_loc('Deer Valley',       $ut_base,
			           'DVR', 40.63139, -111.47861,
			           report_url('deer_valley_resort')),
			create_loc('Park City',         $ut_base,
			           'PCM', 40.64361, -111.50417,
			           report_url('park_city_mountain_resort')),
			create_loc('Powder Mountain',   $ut_base,
			           'POW', 41.37778, -111.77111,
			           report_url('powder_mountain_resort')),
			create_loc('Snowbasin',         $ut_base,
			           'SBN', 41.21194, -111.85111,
			           report_url('snowbasin')),
			create_loc('Snowbird',           $ut_base,
			           'SBD', 40.57805, -111.666755,
			           report_url('snowbird_ski_and_summer_resort')),
			create_loc('Solitude',          $ut_base,
			           'SOL', 40.62556, -111.59444,
			           report_url('solitude_mountain_resort')),
			create_loc('Sundance',          $ut_base,
			           'SUN', 40.38583, -111.58083,
			           report_url('sundance_resort')),
			create_loc('Wolf Mountain',        $ut_base,
			           'WLF', 40.47667, -111.02361,
			           report_url('wolf_mountain')),
		));
}

function resorts_ut_get()
{
	return region_create_ut()->locations;
}
