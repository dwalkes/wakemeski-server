<?php

require_once('common.inc');

function region_create_co()
{
	$co_base = 'http://bettykrocks.com/skireport/co_report.php';
	$co2_base = 'http://bettykrocks.com/skireport/co_report2.php';

	return create_region("Colorado",
		array(
			create_loc('Arapahoe Basin',     $co_base,
			           'AB', 39.6448, -105.871),
			create_loc('Aspen Highlands',    $co_base,
			           'AH', 39.181711, -106.856121),
			create_loc('Aspen Mountain',     $co_base,
			           'AM', 39.18428,  -106.821903),
			create_loc('Buttermilk',         $co_base,
			           'BM', 39.205167, -106.859294),
			create_loc('Copper Mountain',    $co_base,
			           'CM', 39.4944,   -106.138732),
			create_loc('Crested Butte',      $co_base,
			           'CB', 38.899932, -106.964249),
			create_loc('Echo Mountain',      $co_base,
			           'EM', 37.591389, -107.571726),
			create_loc('Eldora',             $co_base,
			           'EL', 39.937341, -105.5853),
			create_loc('Howelsen',           $co_base,
			           'HW', 40.480533, -106.840605),
			create_loc('Loveland',           $co_base,
			           'LV', 39.680191, -105.898114),
			create_loc('Monarch Mountain',   $co_base,
			           'MM', 38.512285, -106.332957),
			create_loc('Powderhorn',         $co_base,
			           'PH', 39.068912, -108.15068),
			create_loc('Purgatory',          $co_base,
			           'PG', 37.629261, -107.815288),
			create_loc('Silverton Mountain', $co_base,
			           'SM', 37.791067, -107.666171),
			create_loc('Ski Cooper',         $co_base,
			           'SC', 39.358897, -106.299256),
			create_loc('Snowmass',           $co_base,
			           'SN', 39.162132, -106.787847),
			create_loc('Sol Vista Basin',    $co_base,
			           'SV', 40.04784,  -105.898969),
			create_loc('Steamboat',          $co_base,
			           'ST', 40.458905, -106.802092),
			create_loc('Sunlight',           $co_base,
			           'SL', 39.398121, -107.339174),
			create_loc('Telluride',          $co_base,
			           'TD', 37.9392,   -107.8163),
			create_loc('Winter Park',        $co_base,
			           'WP', 39.886791, -105.764279),
			create_loc('Wolf Creek',         $co_base,
			           'WC', 37.472654, -106.793116),
			create_loc('Vail',              $co2_base,
			           'VA', 39.639423,-106.371, 'http://www.vail.com'),
			create_loc('Beaver Creek',      $co2_base,
			           'BC', 39.60253, -106.5171, 'http://www.beavercreek.com'),
			create_loc('Keystone',          $co2_base,
			           'KS', 39.60402, -105.95433, 'http://www.keystoneresort.com'),
			create_loc('Breckenridge',      $co2_base,
			           'BK', 39.474249,-106.04881, 'http://www.breckenridge.com'),
		));
}

function resorts_co_get()
{
	return region_create_co()->locations;
}
