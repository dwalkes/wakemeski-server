<?php

require_once('common.inc');

function region_create_mt()
{
	$mt_base = 'mt_report.php';
	$fresh = 'http://wintermt.com/skiareas/conditions/snow.asp?id=';

	return create_region("Montana",
		array(
			create_loc('Maverick Mountain',  $mt_base,
			           'MM', 45.434924, -113.1294876, 
			           'http://skimaverick.com/', '9'),
			create_loc('Lost Trail Powder Mountain', $mt_base,
			           'LM', 45.692912, -113.95166,
			           'http://www.losttrail.com/', '7'),
			create_loc('Bear Paw Ski Bowl',  $mt_base,
			           'BP', 48.164456, -109.670357,
			           'http://www.skibearpaw.com/', '1'),
			create_loc('Lookout Pass',       $mt_base,
			           'LP', 47.456233, -115.696404,
			           'http://www.skilookout.com/', '15'),
			create_loc('Discovery',          $mt_base,
			           'DS', 46.248783, -113.239448,
			           'http://www.skidiscovery.com/', '5'),
			create_loc('Blacktail Mountain', $mt_base,
			           'BT', 48.014908, -114.369712,
		               'http://www.blacktailmountain.com/', '16'),
			create_loc('Big Sky',            $mt_base,
			           'BS', 45.284,    -111.402151,
			           'http://www.bigskyresort.com/', '3'),
			create_loc('Great Divide',       $mt_base,
			           'GD', 46.752688, -112.312891,
			           'http://skigd.com/', '6'),
			create_loc('Showdown',           $mt_base,
			           'SD', 46.838133, -110.698483,
			           'http://www.showdownmontana.com/', '12'),
//			create_loc('Teton Pass',         $mt_base,
//			           'TP', 47.928807, -112.805196),
			create_loc('Whitefish',          $mt_base,
			           'WF', 48.484887, -114.353367,
			           'http://www.skiwhitefish.com/', '2'),
			create_loc('Red Lodge',          $mt_base,
			           'RL', 45.190749, -109.336372,
			           'http://www.redlodgemountain.com/', '11'),
			create_loc('Bridger Bowl',       $mt_base,
			           'BB', 45.817659, -110.8958,
			           'http://www.bridgerbowl.com/', '4'),
			create_loc('Montana Snowbowl',   $mt_base,
			           'MS', 47.013869, -113.999649,
			           'http://www.montanasnowbowl.com/', '10'),
			create_loc('Moonlight Basin',    $mt_base,
			           'MB', 45.311904, -111.436659,
			           'http://www.moonlightbasin.com/', '18'),
		));
}

function resorts_mt_get()
{
	return region_create_mt()->locations;
}
