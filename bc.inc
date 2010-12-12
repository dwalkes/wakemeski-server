<?php

require_once('common.inc');

function region_create_bc()
{
	return create_region("British Columbia",
		array(
			create_loc('Whistler',     'bc_whistler_report.php',
			           'WH', 50, -122,
			           'http://www.whistlerblackcomb.com/weather/snowreport/index.htm'),
			create_loc('Revelstoke',   'bc_revelstoke_report.php',
			           'RS', 50.954167, -118.154167,
			           'http://www.revelstokemountainresort.com/mobile/',
			           'http://www.revelstokemountainresort.com/mobile/Snow-Report.aspx'
			),
		));
}

function resorts_bc_get()
{
	return region_create_bc()->locations;
}

/**
 * Translates the canadian report value into one recognized by the android
 * client
 */
function bc_get_weather_icon($ca_val)
{
	switch($ca_val)
	{
		case '00': //sunny
			return 'skc';
		case '02': //sun and clouds
			return 'sct';
		case '03': //cloudy with sunny periods
		case '04': //increasing clouds
			return 'sct';
		case '06': //chance of showers
			return 'scttsra';
		case '08': //chance of flurries (day)
			return 'sn';
		case '10':
			return 'ovc';
		case '12': //chance of rain
			return 'ra';
		case '16': //chance of flurries
		case '17':
		case '18':
			return 'sn';
		case '16':
			return 'mix';
		case '30': //clear night
			return 'nskc';
		case '37': //night cloudy
			return 'ovc';
	}
	
	return $ca_val;
}
